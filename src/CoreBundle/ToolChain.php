<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
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
    /**
     * @var AbstractTool[]|mixed[]
     */
    protected array $tools = [];
    /**
     * @var mixed[]
     */
    protected array $typeList = [];

    protected EntityManagerInterface $entityManager;

    protected SettingsManager $settingsManager;

    protected Security $security;
    /**
     * @var mixed[]
     */
    protected array $repoEntityList = [];

    public function __construct(EntityManagerInterface $entityManager, SettingsManager $settingsManager, Security $security)
    {
        $this->tools = [];
        $this->typeList = [];
        $this->repoEntityList = [];
        $this->entityManager = $entityManager;
        $this->settingsManager = $settingsManager;
        $this->security = $security;
    }

    public function addTool(AbstractTool $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
        if ($tool->getResourceTypes()) {
            foreach ($tool->getResourceTypes() as $key => $type) {
                $this->typeList[$type['repository']] = $key;
            }
        }
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function createTools(): void
    {
        $manager = $this->entityManager;
        $tools = $this->getTools();
        $repo = $manager->getRepository(Tool::class);

        /** @var AbstractTool $tool */
        foreach ($tools as $tool) {
            $name = $tool->getName();
            $toolFromDatabase = $repo->findOneBy([
                'name' => $name,
            ]);
            $toolEntity = new Tool();

            if (null !== $toolFromDatabase) {
                $toolEntity = $toolFromDatabase;
            } else {
                $toolEntity->setName($name);
                if ($tool->isCourseTool()) {
                    $this->setToolPermissions($toolEntity);
                }
                $manager->persist($toolEntity);
            }

            $types = $tool->getResourceTypes();
            if (!empty($types)) {
                foreach (array_keys($types) as $name) {
                    $resourceType = new ResourceType();
                    $resourceType->setName($name);
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
        $toolResourceRight = new ToolResourceRight();
        $toolResourceRight
            ->setRole('ROLE_TEACHER')
            ->setMask(ResourceNodeVoter::getEditorMask())
        ;

        $toolResourceRightReader = new ToolResourceRight();
        $toolResourceRightReader
            ->setRole('ROLE_STUDENT')
            ->setMask(ResourceNodeVoter::getReaderMask())
        ;

        //$tool->addToolResourceRight($toolResourceRight);
        //$tool->addToolResourceRight($toolResourceRightReader);
    }

    public function addToolsInCourse(Course $course): Course
    {
        $tools = $this->getTools();
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
            'wiki',
            'notebook',
            'blog',
            'course_tool',
            'tracking',
            'course_setting',
            'course_maintenance',
        ];
        $toolList = array_flip($toolList);

        $toolRepo = $manager->getRepository(Tool::class);

        /** @var AbstractTool $tool */
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

            $courseTool = new CTool();
            $courseTool
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

    /**
     * @return AbstractTool
     */
    public function getToolFromName(string $name)
    {
        $tools = $this->getTools();

        if (\array_key_exists($name, $tools)) {
            return $tools[$name];
        }

        throw new InvalidArgumentException(sprintf("The Tool '%s' doesn't exist.", $name));
    }

    public function getResourceTypeNameFromRepository(string $repo): string
    {
        if (isset($this->typeList[$repo]) && !empty($this->typeList[$repo])) {
            return $this->typeList[$repo];
        }

        throw new InvalidArgumentException(sprintf("The Resource type '%s' doesn't exist.", $repo));
    }
}
