<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20201215141131 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;
    private const string ITEM_PROPERTY_INDEX = 'idx_ricky_migration_item_property_tool_ref_course';

    public function getDescription(): string
    {
        return 'Migrate c_link_category, c_link';
    }

    public function up(Schema $schema): void
    {
        $this->ensureItemPropertyMigrationIndex();

        $linkRepo = $this->container->get(CLinkRepository::class);
        $linkCategoryRepo = $this->container->get(CLinkCategoryRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);

        $admin = $this->getAdmin();
        $adminId = (int) $admin->getId();
        if ($adminId <= 0) {
            throw new RuntimeException('Unable to resolve the migration administrator.');
        }

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT pending.c_id
             FROM (
                 SELECT c_id FROM c_link_category WHERE resource_node_id IS NULL
                 UNION
                 SELECT c_id FROM c_link WHERE resource_node_id IS NULL
             ) pending
             INNER JOIN course c ON c.id = pending.c_id
             WHERE pending.c_id IS NOT NULL
             ORDER BY pending.c_id'
        );

        $this->getLogger()->info('Starting link resource migration.', [
            'courses' => \count($courseIds),
            'flush_batch_size' => self::ORM_FLUSH_BATCH_SIZE,
        ]);

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            if ($courseId <= 0) {
                continue;
            }

            $this->migrateCategoriesForCourse($courseId, $adminId, $courseRepo, $linkCategoryRepo);
            $this->migrateLinksForCourse($courseId, $adminId, $courseRepo, $linkRepo, $linkCategoryRepo);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Completed link resource migration.', [
            'pending_categories' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_link_category WHERE resource_node_id IS NULL'
            ),
            'pending_links' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_link WHERE resource_node_id IS NULL'
            ),
        ]);
    }

    private function migrateCategoriesForCourse(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        CLinkCategoryRepository $repository
    ): void {
        $ids = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT iid
                 FROM c_link_category
                 WHERE c_id = :courseId
                   AND resource_node_id IS NULL
                 ORDER BY iid',
                ['courseId' => $courseId]
            )
        );

        if ([] === $ids) {
            return;
        }

        $itemProperties = $this->fetchItemPropertiesMap('link_category', $courseId, $ids);
        $processed = 0;
        [$course, $admin, $resourceType] = $this->reloadLinkContext($courseId, $adminId, $courseRepo, $repository);

        foreach ($ids as $id) {
            $resource = $repository->find($id);
            if (!$resource instanceof CLinkCategory || $resource->hasResourceNode()) {
                continue;
            }

            $items = $itemProperties[$id] ?? [];
            if ([] === $items) {
                $this->logItemPropertyInconsistency('link_category', $id, (string) $resource);
                $this->getLogger()->warning('Link category skipped: missing c_item_property.', [
                    'course_id' => $courseId,
                    'category_id' => $id,
                ]);

                continue;
            }

            $result = $this->fixItemProperty(
                'link_category',
                $repository,
                $course,
                $admin,
                $resource,
                $course,
                $items,
                $resourceType
            );

            if (false === $result) {
                continue;
            }

            ++$processed;
            if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                [$course, $admin, $resourceType] = $this->reloadLinkContext(
                    $courseId,
                    $adminId,
                    $courseRepo,
                    $repository
                );
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Link categories migrated.', [
            'course_id' => $courseId,
            'candidates' => \count($ids),
            'migrated' => $processed,
        ]);
    }

    private function migrateLinksForCourse(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        CLinkRepository $repository,
        CLinkCategoryRepository $linkCategoryRepo
    ): void {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT iid, category_id
             FROM c_link
             WHERE c_id = :courseId
               AND resource_node_id IS NULL
             ORDER BY iid',
            ['courseId' => $courseId]
        );

        if ([] === $rows) {
            return;
        }

        $ids = array_map(static fn (array $r): int => (int) $r['iid'], $rows);
        $categoryIdByLinkId = [];
        foreach ($rows as $r) {
            $categoryIdByLinkId[(int) $r['iid']] = (int) ($r['category_id'] ?? 0);
        }

        $itemProperties = $this->fetchItemPropertiesMap('link', $courseId, $ids);
        $processed = 0;
        [$course, $admin, $resourceType] = $this->reloadLinkContext($courseId, $adminId, $courseRepo, $repository);

        foreach ($ids as $id) {
            $resource = $repository->find($id);
            if (!$resource instanceof CLink || $resource->hasResourceNode()) {
                continue;
            }

            $items = $itemProperties[$id] ?? [];
            if ([] === $items) {
                $this->logItemPropertyInconsistency('link', $id, (string) $resource);
                $this->getLogger()->warning('Link skipped: missing c_item_property.', [
                    'course_id' => $courseId,
                    'link_id' => $id,
                ]);

                continue;
            }

            $parent = $course;
            $categoryId = $categoryIdByLinkId[$id] ?? 0;
            if ($categoryId > 0) {
                $category = $linkCategoryRepo->find($categoryId);
                if (null !== $category) {
                    $parent = $category;
                }
            }

            $result = $this->fixItemProperty(
                'link',
                $repository,
                $course,
                $admin,
                $resource,
                $parent,
                $items,
                $resourceType
            );

            if (false === $result) {
                continue;
            }

            ++$processed;
            if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                [$course, $admin, $resourceType] = $this->reloadLinkContext(
                    $courseId,
                    $adminId,
                    $courseRepo,
                    $repository
                );
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Links migrated.', [
            'course_id' => $courseId,
            'candidates' => \count($ids),
            'migrated' => $processed,
        ]);
    }

    /**
     * @param CLinkCategoryRepository|CLinkRepository $repository
     */
    private function reloadLinkContext(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        $repository
    ): array {
        $course = $courseRepo->find($courseId);
        if (!$course instanceof Course) {
            throw new RuntimeException("Course {$courseId} could not be reloaded.");
        }

        $admin = $this->entityManager->getReference(User::class, $adminId);
        $resourceType = $repository->getResourceType();

        return [$course, $admin, $resourceType];
    }

    private function ensureItemPropertyMigrationIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('c_item_property', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('c_item_property') as $index) {
                if (self::ITEM_PROPERTY_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if (\count($columns) >= 2
                    && 'tool' === $columns[0]
                    && 'ref' === $columns[1]
                ) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary migration index on c_item_property.', [
                'index' => self::ITEM_PROPERTY_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::ITEM_PROPERTY_INDEX.' ON c_item_property (tool, ref, c_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create c_item_property migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
