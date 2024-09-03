<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ToolRepository;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\DBAL\Schema\Schema;

final class Version20240509123200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Ensure all courses have the required tools post-migration.';
    }

    public function up(Schema $schema): void
    {
        $courseRepo = $this->container->get(CourseRepository::class);
        $toolRepo = $this->container->get(ToolRepository::class);
        $admin = $this->getAdmin();

        // Define your tool list
        $requiredTools = [
            'course_description',
            'document',
            'learnpath',
            'link',
            'quiz',
            'announcement',
            'gradebook',
            'glossary',
            'attendance',
            'course_progress',
            'agenda',
            'forum',
            'dropbox',
            'member',
            'group',
            'chat',
            'student_publication',
            'survey',
            'wiki',
            'notebook',
            'course_tool',
            'course_homepage',
            'tracking',
            'course_setting',
            'course_maintenance',
        ];

        $courses = $courseRepo->findAll();
        foreach ($courses as $course) {
            foreach ($requiredTools as $toolName) {
                $ctool = $course->getTools()->filter(
                    fn (CTool $ct) => $ct->getTool()->getTitle() === $toolName
                )->first() ?? null;

                if (!$ctool) {
                    $tool = $toolRepo->findOneBy(['title' => $toolName]);
                    if ($tool) {
                        $linkVisibility = ('course_setting' == $toolName || 'course_maintenance' == $toolName)
                            ? ResourceLink::VISIBILITY_DRAFT : ResourceLink::VISIBILITY_PUBLISHED;

                        $ctool = new CTool();
                        $ctool->setTool($tool);
                        $ctool->setTitle($toolName);
                        $ctool->setVisibility(true);
                        $ctool->setParent($course);
                        $ctool->setCreator($admin);
                        $ctool->addCourseLink($course, null, null, $linkVisibility);
                        $this->entityManager->persist($ctool);
                        error_log("Tool '{$toolName}' needs to be added to course ID {$course->getId()}.");

                        $course->addTool($ctool);
                        error_log("Tool '{$toolName}' created and linked to course.");
                    }
                }
            }
        }
        $this->entityManager->flush();
    }
}
