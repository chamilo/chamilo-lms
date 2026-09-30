<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20201216120654 extends AbstractMigrationChamilo
{
    private const int ORM_FLUSH_BATCH_SIZE = 100;
    private const string CGLOSSARY_CID_INDEX = 'idx_legacy_migration_cglossary_c_id';
    private const string RESOURCE_NODE_SLUG_INDEX = 'idx_legacy_migration_resource_node_slug';

    public function getDescription(): string
    {
        return 'Migrate c_glossary';
    }

    /**
     * Glossary items are committed in explicit ORM batches.
     * This makes the migration resumable and avoids losing hours of work if
     * the process is interrupted.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->ensureCGlossaryCidIndex();
        $this->ensureResourceNodeSlugIndex();
        $this->ensureGlossaryCategorySchema();

        $glossaryRepo = $this->container->get(CGlossaryRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $adminId = (int) $this->getAdmin()->getId();
        $courseIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT g.c_id
             FROM c_glossary g
             INNER JOIN course c ON c.id = g.c_id
             WHERE g.resource_node_id IS NULL
             ORDER BY g.c_id'
        );

        foreach ($courseIds as $courseIdValue) {
            $courseId = (int) $courseIdValue;
            [$course, $admin] = $this->reloadGlossaryContext($courseId, $adminId, $courseRepo, $userRepo);

            $glossaryIds = $this->connection->fetchFirstColumn(
                'SELECT iid FROM c_glossary WHERE c_id = :courseId AND resource_node_id IS NULL ORDER BY iid',
                ['courseId' => $courseId]
            );

            $itemPropsMap = $this->fetchItemPropertiesMap('glossary', $courseId, array_map('intval', $glossaryIds));

            foreach (array_chunk(array_map('intval', $glossaryIds), self::ORM_FLUSH_BATCH_SIZE) as $idChunk) {
                $resourcesById = [];
                foreach ($glossaryRepo->findBy(['iid' => $idChunk]) as $resourceEntity) {
                    $resourcesById[$resourceEntity->getIid()] = $resourceEntity;
                }

                foreach ($idChunk as $id) {
                    /** @var CGlossary|null $resource */
                    $resource = $resourcesById[$id] ?? null;
                    if (!$resource instanceof CGlossary || $resource->hasResourceNode()) {
                        continue;
                    }

                    $result = $this->fixItemProperty(
                        'glossary',
                        $glossaryRepo,
                        $course,
                        $admin,
                        $resource,
                        $course,
                        $itemPropsMap[$id] ?? [],
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

                [$course, $admin] = $this->reloadGlossaryContext($courseId, $adminId, $courseRepo, $userRepo);
            }
        }
    }

    private function ensureCGlossaryCidIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('c_glossary', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('c_glossary') as $index) {
                if (self::CGLOSSARY_CID_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if ([] !== $columns && 'c_id' === $columns[0]) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary migration index on c_glossary.', [
                'index' => self::CGLOSSARY_CID_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::CGLOSSARY_CID_INDEX.' ON c_glossary (c_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create c_glossary migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensureResourceNodeSlugIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('resource_node', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('resource_node') as $index) {
                if (self::RESOURCE_NODE_SLUG_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if ([] !== $columns && 'slug' === $columns[0]) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating migration index on resource_node slug.', [
                'index' => self::RESOURCE_NODE_SLUG_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::RESOURCE_NODE_SLUG_INDEX.' ON resource_node (slug)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create resource_node slug migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * The CGlossary mapping loaded below already has the category relation, whose schema is
     * only added by V310\Version20260928132000. Create it now, with the same names, so the
     * ORM can read c_glossary during a 1.11.x upgrade; the V310 migration then skips it.
     */
    private function ensureGlossaryCategorySchema(): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['c_glossary_category'])) {
            $this->connection->executeStatement(
                'CREATE TABLE c_glossary_category (
                    iid INT AUTO_INCREMENT NOT NULL,
                    resource_node_id INT DEFAULT NULL,
                    title LONGTEXT NOT NULL,
                    UNIQUE INDEX UNIQ_GLOSSARY_CATEGORY_RESOURCE_NODE (resource_node_id),
                    PRIMARY KEY(iid)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->connection->executeStatement(
                'ALTER TABLE c_glossary_category ADD CONSTRAINT FK_GLOSSARY_CATEGORY_RESOURCE_NODE FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE'
            );
        }

        $hasCategoryColumn = \in_array(
            'category_id',
            array_map(static fn ($column) => strtolower($column->getName()), $schemaManager->listTableColumns('c_glossary')),
            true
        );
        if (!$hasCategoryColumn) {
            $this->getLogger()->notice('Adding glossary category column to c_glossary before migrating it.');
            $this->connection->executeStatement('ALTER TABLE c_glossary ADD category_id INT DEFAULT NULL');
            $this->connection->executeStatement('CREATE INDEX IDX_GLOSSARY_CATEGORY ON c_glossary (category_id)');
            $this->connection->executeStatement(
                'ALTER TABLE c_glossary ADD CONSTRAINT FK_GLOSSARY_CATEGORY FOREIGN KEY (category_id) REFERENCES c_glossary_category (iid) ON DELETE SET NULL'
            );
        }
    }

    /**
     * @return array{0: Course, 1: User}
     */
    private function reloadGlossaryContext(
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
