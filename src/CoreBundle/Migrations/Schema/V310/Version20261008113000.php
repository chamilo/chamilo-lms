<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query;
use RuntimeException;

final class Version20261008113000 extends AbstractMigrationChamilo
{
    private const string TOOL_TITLE = 'toolbox';
    private const string RESOURCE_TYPE_TITLE = 'toolbox_items';
    private const int FLUSH_BATCH_SIZE = 200;

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

        $toolId = $tool->getId();
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

        $created = 0;
        $query = $entityManager->createQuery('SELECT c.id FROM Chamilo\CoreBundle\Entity\Course c');

        foreach ($query->toIterable([], Query::HYDRATE_SCALAR) as $row) {
            $courseId = (int) $row['id'];
            $courseRef = $entityManager->getReference(Course::class, $courseId);

            $exists = (int) $entityManager
                ->createQuery(
                    'SELECT COUNT(ct.iid)
                       FROM Chamilo\CourseBundle\Entity\CTool ct
                      WHERE ct.title = :title
                        AND ct.course = :course'
                )
                ->setParameter('title', self::TOOL_TITLE)
                ->setParameter('course', $courseRef)
                ->getSingleScalarResult()
            ;

            if ($exists > 0) {
                continue;
            }

            $course = $entityManager->find(Course::class, $courseId);
            if (!$course instanceof Course) {
                continue;
            }

            $toolRef = $entityManager->getReference(Tool::class, $toolId);
            $courseTool = (new CTool())
                ->setTool($toolRef)
                ->setTitle(self::TOOL_TITLE)
                ->setCourse($course)
                ->setParent($course)
                ->setCreator($course->getCreator())
                ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_DRAFT)
            ;
            $entityManager->persist($courseTool);

            ++$created;
            if (0 === $created % self::FLUSH_BATCH_SIZE) {
                $entityManager->flush();
                $entityManager->clear();
            }
        }

        $entityManager->flush();
        $entityManager->clear();

        $this->write(\sprintf(
            'AI Toolbox repair completed: tool and resource type ensured, %d missing course tools created as draft.',
            $created
        ));
    }

    public function down(Schema $schema): void
    {
        $this->write('AI Toolbox repair is intentionally not reverted.');
    }
}
