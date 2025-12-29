<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Class ToolChain.
 *
 * The course tools classes (agenda, blog, etc) are located in:
 *
 * src/Chamilo/CourseBundle/Tool
 *
 * All this classes are registered as a service with the tag "chamilo_core.tool" here:
 *
 * src/Chamilo/CoreBundle/Resources/config/tools.yml
 *
 * The register process is made using the class ToolCompilerClass:
 *
 * src/Chamilo/CoreBundle/DependencyInjection/Compiler/ToolCompilerClass.php
 *
 * The tool chain is just an array that includes all the tools registered in services.yml
 *
 * The tool chain is hook when a new course is created via a listener here:
 *
 * src/Chamilo/CoreBundle/Entity/Listener/CourseListener.php
 *
 * After a course is created this function is called: CourseListener::prePersist()
 * This function includes the called to the function "addToolsInCourse" inside the tool chain.
 *
 * This allows to tools more easily. Steps:
 *
 * 1. Create a new tool class here: src/Chamilo/CoreBundle/Tool
 * 2. Add the class as a service here: src/Chamilo/CoreBundle/Resources/config/tools.yml  (see examples there)
 * 3. Create a new course. When you create a new course the new tool will be created.
 */
class ToolChain
{
    protected EntityManagerInterface $entityManager;

    protected SettingsManager $settingsManager;

    protected Security $security;

    protected HandlerCollection $handlerCollection;

    /**
     * @var string[]
     */
    private array $resourceTypeList = [];

    public function __construct(EntityManagerInterface $entityManager, SettingsManager $settingsManager, Security $security, HandlerCollection $handlerCollection)
    {
        $this->entityManager = $entityManager;
        $this->settingsManager = $settingsManager;
        $this->security = $security;
        $this->handlerCollection = $handlerCollection;
    }

    public function createTools(): void
    {
        $manager = $this->entityManager;
        $tools = $this->handlerCollection->getCollection();
        $toolRepo = $manager->getRepository(Tool::class);

        foreach ($tools as $tool) {
            $title = $tool->getTitle();
            $toolFromDatabase = $toolRepo->findOneBy([
                'title' => $title,
            ]);

            if (null !== $toolFromDatabase) {
                $toolEntity = $toolFromDatabase;
            } else {
                $toolEntity = (new Tool())
                    ->setTitle($title)
                ;
                if ($tool->isCourseTool()) {
                    $this->setToolPermissions($toolEntity);
                }
                $manager->persist($toolEntity);
            }

            $types = $tool->getResourceTypes();

            if (!empty($types)) {
                foreach ($types as $key => $typeTitle) {
                    $resourceType = (new ResourceType())
                        ->setTitle($key)
                    ;

                    if ($toolEntity->hasResourceType($resourceType)) {
                        continue;
                    }
                    $resourceType->setTool($toolEntity);
                    $manager->persist($resourceType);
                }
            }

            $manager->flush();
        }
    }

    public function setToolPermissions(Tool $tool): void
    {
        $toolResourceRight = (new ToolResourceRight())
            ->setRole('ROLE_TEACHER')
            ->setMask(ResourceNodeVoter::getEditorMask())
        ;

        $toolResourceRightReader = (new ToolResourceRight())
            ->setRole('ROLE_STUDENT')
            ->setMask(ResourceNodeVoter::getReaderMask())
        ;

        // $tool->addToolResourceRight($toolResourceRight);
        // $tool->addToolResourceRight($toolResourceRightReader);
    }

    public function addToolsInCourse(Course $course): Course
    {
        $manager = $this->entityManager;
        $activeToolsOnCreate = $this->settingsManager->getSetting('course.active_tools_on_create') ?? [];

        // Hardcoded tool list order
        $toolList = [
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
            'blog',
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
            'portfolio',
        ];
        $toolList = array_flip($toolList);

        $toolRepo = $manager->getRepository(Tool::class);

        $tools = $this->handlerCollection->getCollection();

        foreach ($tools as $tool) {
            $criteria = [
                'title' => $tool->getTitle(),
            ];
            if (!isset($toolList[$tool->getTitle()])) {
                continue;
            }

            $visibility = \in_array($tool->getTitle(), $activeToolsOnCreate, true);
            $linkVisibility = $visibility ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT;

            if (\in_array($tool->getTitle(), ['course_setting', 'course_maintenance'])) {
                $linkVisibility = ResourceLink::VISIBILITY_DRAFT;
            }

            /** @var Tool $toolEntity */
            $toolEntity = $toolRepo->findOneBy($criteria);
            if ($toolEntity) {
                $courseTool = (new CTool())
                    ->setTool($toolEntity)
                    ->setTitle($tool->getTitle())
                    ->setParent($course)
                    ->setCreator($course->getCreator())
                    ->addCourseLink($course, null, null, $linkVisibility)
                ;
                $course->addTool($courseTool);
            }
        }

        return $course;
    }

    public function getTools(): iterable
    {
        return $this->handlerCollection->getCollection();
    }

    public function getToolFromName(string $title): AbstractTool
    {
        foreach ($this->handlerCollection->getCollection() as $handler) {
            if (0 === strcasecmp($handler->getTitle(), $title)) {
                return $handler;
            }
        }

        throw new InvalidArgumentException("Tool handler not found for title: $title");
    }

    /*public function getToolFromEntity(string $entityClass): AbstractTool
    {
        return $this->handlerCollection->getHandler($entityClass);
    }*/

    public function getResourceTypeNameByEntity(string $entityClass): ?string
    {
        $title = $this->getResourceTypeList()[$entityClass] ?? null;

        if (null === $title) {
            return null;
        }

        $title = explode('::', $title);

        return $title[1];
    }

    public function getResourceTypeList(): array
    {
        $tools = $this->handlerCollection->getCollection();

        foreach ($tools as $tool) {
            $toolTitle = $tool->getTitle();
            $typeList = $tool->getResourceTypes();
            if (!empty($typeList)) {
                foreach ($typeList as $title => $entityClass) {
                    $this->resourceTypeList[$entityClass] = $toolTitle.'::'.$title;
                }
            }
        }

        return $this->resourceTypeList;
    }
}
