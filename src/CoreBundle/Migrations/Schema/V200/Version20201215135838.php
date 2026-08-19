<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20201215135838 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;
    private const string ITEM_PROPERTY_INDEX = 'idx_legacy_migration_item_property_tool_ref_course';

    public function getDescription(): string
    {
        return 'Migrate c_course_description';
    }

    /**
     * Course descriptions are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->ensureItemPropertyMigrationIndex();

        $courseDescriptionRepo = $this->container->get(CCourseDescriptionRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $adminId = (int) $this->getAdmin()->getId();
        $preservedWithoutItemProperty = 0;

        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT cd.c_id
             FROM c_course_description cd
             INNER JOIN course c ON c.id = cd.c_id
             WHERE cd.resource_node_id IS NULL
               AND cd.c_id IS NOT NULL
             ORDER BY cd.c_id'
        );

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            [$course, $admin] = $this->reloadCourseDescriptionContext($courseId, $adminId, $courseRepo, $userRepo);

            $itemRows = $this->connection->fetchAllAssociative(
                'SELECT iid, session_id
                 FROM c_course_description
                 WHERE c_id = :courseId
                   AND resource_node_id IS NULL
                 ORDER BY iid',
                ['courseId' => $courseId]
            );

            $itemIds = [];
            $legacySessionIdsByIid = [];
            foreach ($itemRows as $itemRow) {
                $iid = (int) $itemRow['iid'];
                $itemIds[] = $iid;
                $legacySessionIdsByIid[$iid] = (int) ($itemRow['session_id'] ?? 0);
            }

            $itemPropsMap = $this->fetchItemPropertiesMap('course_description', $courseId, $itemIds);

            foreach (array_chunk($itemIds, self::ORM_FLUSH_BATCH_SIZE) as $idChunk) {
                $resourcesById = [];
                foreach ($courseDescriptionRepo->findBy(['iid' => $idChunk]) as $resourceEntity) {
                    $resourcesById[$resourceEntity->getIid()] = $resourceEntity;
                }

                foreach ($idChunk as $id) {
                    /** @var CCourseDescription|null $resource */
                    $resource = $resourcesById[$id] ?? null;
                    if (!$resource instanceof CCourseDescription || $resource->hasResourceNode()) {
                        continue;
                    }

                    $itemProperties = $itemPropsMap[$id] ?? [];
                    if (empty($itemProperties)) {
                        // Keep a direct fallback query so a failed prefetch cannot be
                        // mistaken for genuinely missing legacy metadata.
                        $itemProperties = $this->connection->fetchAllAssociative(
                            'SELECT visibility, insert_user_id, session_id, to_group_id, lastedit_date
                             FROM c_item_property
                             WHERE tool = :tool AND c_id = :cid AND ref = :ref',
                            [
                                'tool' => 'course_description',
                                'cid' => $courseId,
                                'ref' => $id,
                            ]
                        );
                    }

                    if (empty($itemProperties) && 0 === ($legacySessionIdsByIid[$id] ?? 0)) {
                        // Chamilo 1.11.x lists course-scope descriptions directly from
                        // c_course_description. Preserve those historical rows even when
                        // their c_item_property metadata is missing instead of dropping
                        // them from the Chamilo 2 resource model.
                        $resource->setParent($course);
                        $courseDescriptionRepo->addResourceNode($resource, $admin, $course, null, false);
                        $resource->addCourseLink(
                            $course,
                            null,
                            null,
                            ResourceLink::VISIBILITY_PUBLISHED
                        );
                        $this->entityManager->persist($resource);
                        ++$preservedWithoutItemProperty;

                        continue;
                    }

                    $result = $this->fixItemProperty(
                        'course_description',
                        $courseDescriptionRepo,
                        $course,
                        $admin,
                        $resource,
                        $course,
                        $itemProperties,
                        null,
                        false
                    );

                    if (false === $result) {
                        continue;
                    }

                    $this->entityManager->persist($resource);
                }

                $this->entityManager->flush();
                $this->entityManager->clear();

                [$course, $admin] = $this->reloadCourseDescriptionContext(
                    $courseId,
                    $adminId,
                    $courseRepo,
                    $userRepo
                );
            }
        }

        if ($preservedWithoutItemProperty > 0) {
            $this->getLogger()->warning(
                'Preserved course descriptions without c_item_property as published course resources.',
                ['count' => $preservedWithoutItemProperty]
            );
        }
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

    /**
     * @return array{0: Course, 1: User}
     */
    private function reloadCourseDescriptionContext(
        int $courseId,
        int $adminId,
        CourseRepository $courseRepo,
        UserRepository $userRepo
    ): array {
        $course = $courseRepo->find($courseId);
        $admin = $userRepo->find($adminId);

        if (!$course instanceof Course) {
            throw new RuntimeException("Course {$courseId} could not be reloaded.");
        }

        if (!$admin instanceof User) {
            throw new RuntimeException("Admin user {$adminId} could not be reloaded.");
        }

        return [$course, $admin];
    }
}
