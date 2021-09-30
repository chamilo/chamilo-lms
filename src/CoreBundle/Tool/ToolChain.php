<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

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
            $name = $tool->getName();
            $toolFromDatabase = $toolRepo->findOneBy([
                'name' => $name,
            ]);

            if (null !== $toolFromDatabase) {
                $toolEntity = $toolFromDatabase;
            } else {
                $toolEntity = (new Tool())
                    ->setName($name)
                ;
                if ($tool->isCourseTool()) {
                    $this->setToolPermissions($toolEntity);
                }
                $manager->persist($toolEntity);
            }

            $types = $tool->getResourceTypes();

            if (!empty($types)) {
                foreach ($types as $key => $typeName) {
                    $resourceType = (new ResourceType())
                        ->setName($key)
                    ;

                    if ($toolEntity->hasResourceType($resourceType)) {
                        continue;
                    }
                    $resourceType->setTool($toolEntity);
                    $manager->persist($resourceType);
                }
                $manager->flush();
            }
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

        //$tool->addToolResourceRight($toolResourceRight);
        //$tool->addToolResourceRight($toolResourceRightReader);
    }

    public function addToolsInCourse(Course $course): Course
    {
        $manager = $this->entityManager;
        $toolVisibility = $this->settingsManager->getSetting('course.active_tools_on_create');

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
            'member',
            'group',
            'chat',
            'student_publication',
            'survey',
            //'wiki',
            'notebook',
            //'blog',
            'course_tool',
            'course_homepage',
            'tracking',
            'course_setting',
            'course_maintenance',
        ];
        $toolList = array_flip($toolList);

        $toolRepo = $manager->getRepository(Tool::class);

        $tools = $this->handlerCollection->getCollection();

        foreach ($tools as $tool) {
            $visibility = \in_array($tool->getName(), $toolVisibility, true);
            $criteria = [
                'name' => $tool->getName(),
            ];
            if (!isset($toolList[$tool->getName()])) {
                continue;
            }

            /** @var Tool $toolEntity */
            $toolEntity = $toolRepo->findOneBy($criteria);
            $position = $toolList[$tool->getName()] + 1;

            $courseTool = (new CTool())
                ->setTool($toolEntity)
                ->setName($tool->getName())
                ->setPosition($position)
                ->setVisibility($visibility)
                ->setParent($course)
                ->setCreator($course->getCreator())
                ->addCourseLink($course)
            ;
            $course->addTool($courseTool);
        }

        return $course;
    }

    public function getTools()
    {
        return $this->handlerCollection->getCollection();
    }

    public function getToolFromName(string $name): AbstractTool
    {
        return $this->handlerCollection->getHandler($name);
    }

    /*public function getToolFromEntity(string $entityClass): AbstractTool
    {
        return $this->handlerCollection->getHandler($entityClass);
    }*/

    public function getResourceTypeNameByEntity(string $entityClass): ?string
    {
        $name = $this->getResourceTypeList()[$entityClass] ?? null;

        if (null === $name) {
            return null;
        }

        $name = explode('::', $name);

        return $name[1];
    }

    public function getResourceTypeList(): array
    {
        $tools = $this->handlerCollection->getCollection();

        foreach ($tools as $tool) {
            $toolName = $tool->getName();
            $typeList = $tool->getResourceTypes();
            if (!empty($typeList)) {
                foreach ($typeList as $name => $entityClass) {
                    $this->resourceTypeList[$entityClass] = $toolName.'::'.$name;
                }
            }
        }

        return $this->resourceTypeList;
    }
}
