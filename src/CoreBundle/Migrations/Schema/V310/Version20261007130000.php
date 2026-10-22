<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query;

final class Version20261007130000 extends AbstractMigrationChamilo
{
    private const string TOOL_TITLE = 'teams';
    private const int FLUSH_BATCH_SIZE = 200;

    public function getDescription(): string
    {
        return 'Register the Microsoft Teams course tool and seed it in existing courses.';
    }

    public function up(Schema $schema): void
    {
        /** @var ToolChain $toolChain */
        $toolChain = $this->container->get(ToolChain::class);
        $toolChain->createTools();

        $toolId = $this->entityManager
            ->createQuery('SELECT t.id FROM Chamilo\CoreBundle\Entity\Tool t WHERE t.title = :title')
            ->setParameter('title', self::TOOL_TITLE)
            ->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR)
        ;

        if (null === $toolId) {
            $this->write('Microsoft Teams tool definition was not created; course-tool seeding skipped.');

            return;
        }

        $toolId = (int) $toolId;
        $created = 0;

        $query = $this->entityManager->createQuery('SELECT c.id FROM Chamilo\CoreBundle\Entity\Course c');

        foreach ($query->toIterable([], Query::HYDRATE_SCALAR) as $row) {
            $courseId = (int) $row['id'];
            $courseRef = $this->entityManager->getReference(Course::class, $courseId);

            $exists = (int) $this->entityManager
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

            $course = $this->entityManager->find(Course::class, $courseId);
            if (!$course instanceof Course) {
                continue;
            }

            $toolRef = $this->entityManager->getReference(Tool::class, $toolId);
            $courseTool = (new CTool())
                ->setTool($toolRef)
                ->setTitle(self::TOOL_TITLE)
                ->setCourse($course)
                ->setParent($course)
                ->setCreator($course->getCreator())
                ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_DRAFT)
            ;

            $this->entityManager->persist($courseTool);

            ++$created;
            if (0 === $created % self::FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->write(\sprintf('Seeded Microsoft Teams in %d existing course(s) as a draft tool.', $created));
    }

    public function down(Schema $schema): void
    {
        // CTool is an AbstractResource with ResourceNode/ResourceLink lifecycle.
        // Automated rollback would risk orphaning or deleting shared resource metadata,
        // so course-tool cleanup is intentionally not performed here.
        $this->write('Microsoft Teams course-tool/resource-node cleanup is intentionally not automated in down().');
    }
}
