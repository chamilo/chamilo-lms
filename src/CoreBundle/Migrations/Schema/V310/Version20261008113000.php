<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20261008113000 extends AbstractMigrationChamilo
{
    private const string TOOL_TITLE = 'toolbox';
    private const string RESOURCE_TYPE_TITLE = 'toolbox_items';
    private const int FLUSH_BATCH_SIZE = 200;
    private const string TEMP_SLUG_INDEX = 'idx_migration_toolbox_resource_node_slug';

    public function getDescription(): string
    {
        return 'Repair missing AI Toolbox tool registration and seed the course tool on upgraded installations.';
    }

    public function up(Schema $schema): void
    {
        foreach (['tool', 'resource_type', 'course', 'c_tool', 'resource_node', 'resource_link'] as $table) {
            if (!$schema->hasTable($table)) {
                $this->write(\sprintf('Skipping AI Toolbox repair because table "%s" is missing.', $table));

                return;
            }
        }

        $entityManager = $this->entityManager;
        if (null === $entityManager) {
            throw new RuntimeException('The EntityManager is required to repair the AI Toolbox course tool.');
        }

        $tool = $entityManager->getRepository(Tool::class)->findOneBy([
            'title' => self::TOOL_TITLE,
        ]);

        if (!$tool instanceof Tool) {
            $tool = (new Tool())
                ->setTitle(self::TOOL_TITLE)
            ;
            $entityManager->persist($tool);
            $entityManager->flush();
        }

        $toolId = (int) $tool->getId();
        if ($toolId <= 0) {
            throw new RuntimeException('The AI Toolbox tool has no valid identifier.');
        }

        $resourceType = $entityManager->getRepository(ResourceType::class)->findOneBy([
            'title' => self::RESOURCE_TYPE_TITLE,
        ]);

        if ($resourceType instanceof ResourceType) {
            if ($resourceType->getTool()->getId() !== $toolId) {
                throw new RuntimeException(\sprintf('Resource type "%s" already exists but belongs to another tool.', self::RESOURCE_TYPE_TITLE));
            }
        } else {
            $resourceType = (new ResourceType())
                ->setTitle(self::RESOURCE_TYPE_TITLE)
                ->setTool($tool)
            ;
            $entityManager->persist($resourceType);
            $entityManager->flush();
        }

        $courseRows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT c.id, c.resource_node_id, course_node.creator_id
FROM course c
LEFT JOIN resource_node course_node ON course_node.id = c.resource_node_id
LEFT JOIN c_tool existing_tool
    ON existing_tool.c_id = c.id
   AND existing_tool.title = :title
WHERE existing_tool.iid IS NULL
ORDER BY c.id
SQL,
            ['title' => self::TOOL_TITLE]
        );

        if ([] === $courseRows) {
            $this->write('AI Toolbox repair completed: tool and resource type ensured, no missing course tools found.');

            return;
        }

        $dropTemporarySlugIndex = $this->ensureTemporarySlugIndex();
        $created = 0;

        try {
            foreach ($courseRows as $row) {
                $courseId = (int) $row['id'];
                $courseResourceNodeId = (int) $row['resource_node_id'];
                $creatorId = (int) $row['creator_id'];

                if ($courseId <= 0 || $courseResourceNodeId <= 0 || $creatorId <= 0) {
                    throw new RuntimeException(\sprintf('Course %d has no valid resource node/creator; AI Toolbox repair cannot continue safely.', $courseId));
                }

                $courseRef = $entityManager->getReference(Course::class, $courseId);
                $toolRef = $entityManager->getReference(Tool::class, $toolId);
                $creatorRef = $entityManager->getReference(User::class, $creatorId);

                $courseTool = (new CTool())
                    ->setTool($toolRef)
                    ->setTitle(self::TOOL_TITLE)
                    ->setCourse($courseRef)
                    ->setParent($courseRef)
                    ->setParentResourceNode($courseResourceNodeId)
                    ->setCreator($creatorRef)
                    ->addCourseLink($courseRef, null, null, ResourceLink::VISIBILITY_DRAFT)
                ;
                $entityManager->persist($courseTool);

                $resourceNode = $courseTool->getResourceNode();
                if (null === $resourceNode) {
                    throw new RuntimeException(\sprintf('AI Toolbox resource node was not created for course %d.', $courseId));
                }

                // Keep the displayed title unchanged while making the slug lookup
                // selective. The temporary slug index below prevents one full
                // resource_node scan for every course during Gedmo uniqueness checks.
                $resourceNode->setSlug(self::TOOL_TITLE.'-'.$courseId);

                ++$created;
                if (0 === $created % self::FLUSH_BATCH_SIZE) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }

            $entityManager->flush();
            $entityManager->clear();
        } finally {
            if ($dropTemporarySlugIndex) {
                $this->dropTemporarySlugIndex();
            }
        }

        $this->write(\sprintf(
            'AI Toolbox repair completed: tool and resource type ensured, %d missing course tools created as draft.',
            $created
        ));
    }

    public function down(Schema $schema): void
    {
        $this->write('AI Toolbox repair is intentionally not reverted.');
    }

    private function ensureTemporarySlugIndex(): bool
    {
        try {
            $indexes = $this->connection->createSchemaManager()->listTableIndexes('resource_node');

            foreach ($indexes as $index) {
                $name = strtolower($index->getName());
                $columns = array_map('strtolower', $index->getColumns());

                if (strtolower(self::TEMP_SLUG_INDEX) === $name) {
                    return true;
                }

                if ('slug' === ($columns[0] ?? null)) {
                    return false;
                }
            }

            $this->connection->executeStatement(
                'CREATE INDEX '.self::TEMP_SLUG_INDEX.' ON resource_node (slug(191))'
            );

            return true;
        } catch (Throwable $exception) {
            $this->write('Could not create the temporary resource_node.slug index: '.$exception->getMessage());

            return false;
        }
    }

    private function dropTemporarySlugIndex(): void
    {
        try {
            $indexes = $this->connection->createSchemaManager()->listTableIndexes('resource_node');
            foreach ($indexes as $index) {
                if (strtolower(self::TEMP_SLUG_INDEX) !== strtolower($index->getName())) {
                    continue;
                }

                $this->connection->executeStatement(
                    'DROP INDEX '.self::TEMP_SLUG_INDEX.' ON resource_node'
                );

                return;
            }
        } catch (Throwable $exception) {
            $this->write('Could not remove the temporary resource_node.slug index: '.$exception->getMessage());
        }
    }
}
