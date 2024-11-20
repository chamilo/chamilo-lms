<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

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
        return 'Ensure only base tools exist (session_id = NULL) for each course, removing session-specific tools.';
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
                $baseTool = $course->getTools()->filter(
                    fn (CTool $ct) => $ct->getTool()->getTitle() === $toolName && null === $ct->getSession()
                )->first();

                if (!$baseTool) {
                    $tool = $toolRepo->findOneBy(['title' => $toolName]);
                    if ($tool) {
                        $linkVisibility = ('course_setting' == $toolName || 'course_maintenance' == $toolName)
                            ? ResourceLink::VISIBILITY_DRAFT : ResourceLink::VISIBILITY_PUBLISHED;

                        $baseTool = new CTool();
                        $baseTool->setTool($tool);
                        $baseTool->setTitle($toolName);
                        $baseTool->setVisibility(true);
                        $baseTool->setCourse($course);
                        $baseTool->setParent($course);
                        $baseTool->setSession(null);
                        $baseTool->setCreator($admin);
                        $baseTool->addCourseLink($course, null, null, $linkVisibility);

                        $this->entityManager->persist($baseTool);
                        error_log("Base tool '{$toolName}' added for course ID {$course->getId()}.");
                    }
                }
            }

            $sessionTools = $course->getTools()->filter(
                fn (CTool $ct) => null !== $ct->getSession()
            );

            foreach ($sessionTools as $tool) {
                $this->entityManager->remove($tool);
                error_log("Removed session-specific tool '{$tool->getTitle()}' (ID: {$tool->getIid()}).");
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
