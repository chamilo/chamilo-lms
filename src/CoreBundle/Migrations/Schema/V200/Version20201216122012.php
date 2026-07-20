<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20201216122012 extends AbstractMigrationChamilo
{
    private const ORM_FLUSH_BATCH_SIZE = 100;
    private const ITEM_PROPERTY_INDEX = 'idx_ricky_migration_item_property_tool_ref_course';

    public function getDescription(): string
    {
        return 'Migrate c_lp and c_lp_category with prefetched item properties and bounded ORM batches';
    }

    public function up(Schema $schema): void
    {
        $this->ensureItemPropertyMigrationIndex();

        /** @var CLpCategoryRepository $lpCategoryRepo */
        $lpCategoryRepo = $this->container->get(CLpCategoryRepository::class);
        /** @var CLpRepository $lpRepo */
        $lpRepo = $this->container->get(CLpRepository::class);
        /** @var CourseRepository $courseRepo */
        $courseRepo = $this->container->get(CourseRepository::class);

        $admin = $this->getAdmin();
        $adminId = (int) $admin->getId();
        if ($adminId <= 0) {
            throw new RuntimeException('Unable to resolve the migration administrator.');
        }

        $orphanRows = $this->connection->fetchAllAssociative(
            'SELECT
                 pending.c_id,
                 SUM(pending.category_count) AS category_count,
                 SUM(pending.learning_path_count) AS learning_path_count
             FROM (
                 SELECT c_id, COUNT(*) AS category_count, 0 AS learning_path_count
                 FROM c_lp_category
                 WHERE resource_node_id IS NULL
                 GROUP BY c_id
                 UNION ALL
                 SELECT c_id, 0 AS category_count, COUNT(*) AS learning_path_count
                 FROM c_lp
                 WHERE resource_node_id IS NULL
                 GROUP BY c_id
             ) pending
             LEFT JOIN course c ON c.id = pending.c_id
             WHERE pending.c_id IS NOT NULL
               AND c.id IS NULL
             GROUP BY pending.c_id
             ORDER BY pending.c_id'
        );

        foreach ($orphanRows as $orphanRow) {
            $this->getLogger()->warning('Learning-path resources skipped: missing course.', [
                'course_id' => (int) $orphanRow['c_id'],
                'categories' => (int) $orphanRow['category_count'],
                'learning_paths' => (int) $orphanRow['learning_path_count'],
            ]);
        }

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT pending.c_id
             FROM (
                 SELECT c_id FROM c_lp_category WHERE resource_node_id IS NULL
                 UNION
                 SELECT c_id FROM c_lp WHERE resource_node_id IS NULL
             ) pending
             INNER JOIN course c ON c.id = pending.c_id
             WHERE pending.c_id IS NOT NULL
             ORDER BY pending.c_id'
        );

        $this->getLogger()->info('Starting optimized learning-path resource migration.', [
            'courses' => \count($courseIds),
            'flush_batch_size' => self::ORM_FLUSH_BATCH_SIZE,
        ]);

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            if ($courseId <= 0) {
                continue;
            }

            $this->migrateCategoriesForCourse(
                $courseId,
                $adminId,
                $courseRepo,
                $lpCategoryRepo
            );
            $this->migrateLearningPathsForCourse(
                $courseId,
                $adminId,
                $courseRepo,
                $lpRepo
            );
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Completed optimized learning-path resource migration.', [
            'pending_categories' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_lp_category WHERE resource_node_id IS NULL'
            ),
            'pending_learning_paths' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_lp WHERE resource_node_id IS NULL'
            ),
        ]);
    }

    private function migrateCategoriesForCourse(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        CLpCategoryRepository $repository
    ): void {
        $ids = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT iid
                 FROM c_lp_category
                 WHERE c_id = :courseId
                   AND resource_node_id IS NULL
                 ORDER BY iid',
                ['courseId' => $courseId]
            )
        );

        if ([] === $ids) {
            return;
        }

        $itemProperties = $this->fetchItemPropertiesMap(
            'learnpath_category',
            $courseId,
            $ids
        );
        $processed = 0;
        [$course, $admin, $resourceType] = $this->reloadContext(
            $courseId,
            $adminId,
            $courseRepo,
            $repository
        );

        foreach ($ids as $id) {
            $resource = $repository->find($id);
            if (!$resource instanceof CLpCategory || $resource->hasResourceNode()) {
                continue;
            }

            $items = $itemProperties[$id] ?? [];
            if ([] === $items) {
                $this->logItemPropertyInconsistency('learnpath_category', $id, (string) $resource);
                $this->getLogger()->warning('Learning-path category skipped: missing c_item_property.', [
                    'course_id' => $courseId,
                    'category_id' => $id,
                ]);
                continue;
            }

            $result = $this->fixItemProperty(
                'learnpath_category',
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
                [$course, $admin, $resourceType] = $this->reloadContext(
                    $courseId,
                    $adminId,
                    $courseRepo,
                    $repository
                );
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Learning-path categories migrated.', [
            'course_id' => $courseId,
            'candidates' => \count($ids),
            'migrated' => $processed,
        ]);
    }

    private function migrateLearningPathsForCourse(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        CLpRepository $repository
    ): void {
        $ids = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT iid
                 FROM c_lp
                 WHERE c_id = :courseId
                   AND resource_node_id IS NULL
                 ORDER BY iid',
                ['courseId' => $courseId]
            )
        );

        if ([] === $ids) {
            return;
        }

        $itemProperties = $this->fetchItemPropertiesMap('learnpath', $courseId, $ids);
        $rootItemLpIds = $this->loadRootItemLpIds($ids);
        $processed = 0;
        $rootsCreated = 0;
        [$course, $admin, $resourceType] = $this->reloadContext(
            $courseId,
            $adminId,
            $courseRepo,
            $repository
        );

        foreach ($ids as $id) {
            $resource = $repository->find($id);
            if (!$resource instanceof CLp || $resource->hasResourceNode()) {
                continue;
            }

            $items = $itemProperties[$id] ?? [];
            if ([] === $items) {
                $this->logItemPropertyInconsistency('learnpath', $id, (string) $resource);
                $this->getLogger()->warning('Learning path skipped: missing c_item_property.', [
                    'course_id' => $courseId,
                    'lp_id' => $id,
                ]);
                continue;
            }

            $result = $this->fixItemProperty(
                'learnpath',
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

            if (!isset($rootItemLpIds[$id])) {
                $rootItem = (new CLpItem())
                    ->setTitle('root')
                    ->setPath('root')
                    ->setLp($resource)
                    ->setItemType('root')
                ;
                $this->entityManager->persist($rootItem);
                $rootItemLpIds[$id] = true;
                ++$rootsCreated;
            }

            ++$processed;
            if (0 === $processed % self::ORM_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                [$course, $admin, $resourceType] = $this->reloadContext(
                    $courseId,
                    $adminId,
                    $courseRepo,
                    $repository
                );
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Learning paths migrated.', [
            'course_id' => $courseId,
            'candidates' => \count($ids),
            'migrated' => $processed,
            'root_items_created' => $rootsCreated,
        ]);
    }

    /**
     * @param int[] $lpIds
     *
     * @return array<int, true>
     */
    private function loadRootItemLpIds(array $lpIds): array
    {
        if ([] === $lpIds) {
            return [];
        }

        $rows = $this->connection->executeQuery(
            "SELECT DISTINCT lp_id
             FROM c_lp_item
             WHERE lp_id IN (:lpIds)
               AND path = 'root'",
            ['lpIds' => $lpIds],
            ['lpIds' => ArrayParameterType::INTEGER]
        )->fetchFirstColumn();

        $map = [];
        foreach ($rows as $lpId) {
            $map[(int) $lpId] = true;
        }

        return $map;
    }

    /**
     * @return array{0: Course, 1: User, 2: ResourceType}
     */
    private function reloadContext(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        CLpCategoryRepository|CLpRepository $repository
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
