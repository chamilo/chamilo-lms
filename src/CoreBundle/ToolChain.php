<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceLink;
use Chamilo\CoreBundle\Entity\Resource\ResourceType;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Chamilo\SettingsBundle\Manager\SettingsManager;
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

 * src/Chamilo/CoreBundle/Resources/config/tools.yml
 *
 * The register process is made using the class ToolCompilerClass:
 *
 * src/Chamilo/CoreBundle/DependencyInjection/Compiler/ToolCompilerClass.php

 * The tool chain is just an array that includes all the tools registered in services.yml
 *
 * The tool chain is hook when a new course is created via a listener here:

 * src/Chamilo/CoreBundle/Entity/Listener/CourseListener.php

 * After a course is created this function is called: CourseListener::prePersist()
 * This function includes the called to the function "addToolsInCourse" inside the tool chain.

 * This allows to tools more easily. Steps:

 * 1. Create a new tool class here: src/Chamilo/CoreBundle/Tool
 * 2. Add the class as a service here: src/Chamilo/CoreBundle/Resources/config/tools.yml  (see examples there)
 * 3. Create a new course. When you create a new course the new tool will be created.
 */
class ToolChain
{
    protected $tools;
    protected $typeList;
    protected $entityManager;
    protected $settingsManager;
    protected $security;

    public function __construct(EntityManagerInterface $entityManager, SettingsManager $settingsManager, Security $security)
    {
        $this->tools = [];
        $this->typeList = [];
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

        /** @var AbstractTool $tool */
        foreach ($tools as $tool) {
            $toolEntity = new Tool();
            $toolEntity
                ->setName($tool->getName())
            ;

            if ($tool->isCourseTool()) {
                $this->setToolPermissions($toolEntity);
            }

            $manager->persist($toolEntity);

            $types = $tool->getResourceTypes();
            if (!empty($types)) {
                foreach ($types as $name => $data) {
                    $resourceType = new ResourceType();
                    $resourceType->setName($name);
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

        $tool->addToolResourceRight($toolResourceRight);
        $tool->addToolResourceRight($toolResourceRightReader);
    }

    public function addToolsInCourse(CToolRepository $toolRepository, Course $course): Course
    {
        $tools = $this->getTools();
        $manager = $this->entityManager;
        $toolVisibility = $this->settingsManager->getSetting('course.active_tools_on_create');

        $user = $this->security->getToken()->getUser();

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

        /** @var AbstractTool $tool */
        foreach ($tools as $tool) {
            $visibility = in_array($tool->getName(), $toolVisibility, true);
            $criteria = ['name' => $tool->getName()];
            // Skip global tools.
            /*if ($tool->isCourseTool() === false) {
                continue;
            }*/
            if (!isset($toolList[$tool->getName()])) {
                continue;
            }
            $toolEntity = $manager->getRepository('ChamiloCoreBundle:Tool')->findOneBy($criteria);
            $position = $toolList[$tool->getName()] + 1;

            $courseTool = new CTool();
            $courseTool
                ->setTool($toolEntity)
                ->setName($tool->getName())
                ->setPosition($position)
                ->setVisibility($visibility)
                ->setCategory($tool->getCategory())
            ;

            $toolRepository->addResourceToCourse($courseTool, ResourceLink::VISIBILITY_PUBLISHED, $user, $course);
            $course->addTools($courseTool);
        }

        return $course;
    }

    /**
     * @param string $name
     *
     * @return AbstractTool
     */
    public function getToolFromName($name)
    {
        $tools = $this->getTools();

        if (array_key_exists($name, $tools)) {
            return $tools[$name];
        }

        throw new InvalidArgumentException(sprintf("The Tool '%s' doesn't exist.", $name));
    }

    public function getResourceTypeNameFromRepository(string $repo)
    {
        if (isset($this->typeList[$repo]) && !empty($this->typeList[$repo])) {
            return $this->typeList[$repo];
        }

        throw new InvalidArgumentException(sprintf("The Resource type '%s' doesn't exist.", $repo));
    }
}
