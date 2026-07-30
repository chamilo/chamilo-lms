<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
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
        $courseIds = $this->connection->fetchFirstColumn('SELECT id FROM course ORDER BY id');

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            [$course, $admin] = $this->reloadCourseDescriptionContext($courseId, $adminId, $courseRepo, $userRepo);

            $itemIds = $this->connection->fetchFirstColumn(
                'SELECT iid FROM c_course_description WHERE c_id = :courseId AND resource_node_id IS NULL ORDER BY iid',
                ['courseId' => $courseId]
            );

            $itemPropsMap = $this->fetchItemPropertiesMap('course_description', $courseId, array_map('intval', $itemIds));

            foreach (array_chunk(array_map('intval', $itemIds), self::ORM_FLUSH_BATCH_SIZE) as $idChunk) {
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

                    $result = $this->fixItemProperty(
                        'course_description',
                        $courseDescriptionRepo,
                        $course,
                        $admin,
                        $resource,
                        $course,
                        $itemPropsMap[$id] ?? []
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
