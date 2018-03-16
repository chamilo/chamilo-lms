<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Tool\BaseTool;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ToolChain.
 *
 * The course tools classes (agenda, blog, etc) are located in:
 *
 * src/Chamilo/CourseBundle/Tool
 *
 * All this classes are registered as a service with the tag "chamilo_course.tool" here:

 * src/Chamilo/CourseBundle/Resources/config/services.yml
 *
 * The register process is made using the class ToolCompilerClass:
 *
 * src/Chamilo/CourseBundle/DependencyInjection/Compiler/ToolCompilerClass.php

 * The tool chain is just an array that includes all the tools registered in services.yml
 *
 * The tool chain is hook when a new course is created via a listener here:

 * src/Chamilo/CoreBundle/Entity/Listener/CourseListener.php

 * After a course is created this function is called: CourseListener::prePersist()
 * This function includes the called to the function "addToolsInCourse" inside the tool chain.

 * This allows to create course tools more easily. Steps:

 * 1. Create a new tool class here: src/Chamilo/CourseBundle/Tool
 * 2. Add the class as a service here: src/Chamilo/CourseBundle/Resources/config/services.yml  (see examples there)
 * 3. Create a new course. When you create a new course the new tool will be created
 *
 * @package Chamilo\CourseBundle
 */
class ToolChain
{
    protected $tools;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->tools = [];
    }

    /**
     * @param BaseTool $tool
     */
    public function addTool(BaseTool $tool)
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * @return array
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param ObjectManager $manager
     */
    public function createTools(ObjectManager $manager)
    {
        $tools = $this->getTools();
        $toolResourceRight = new ToolResourceRights();
        $toolResourceRight
            ->setRole('ROLE_TEACHER')
            ->setMask(ResourceNodeVoter::getEditorMask())
        ;

        $toolResourceRightReader = new ToolResourceRights();
        $toolResourceRightReader
            ->setRole('ROLE_STUDENT')
            ->setMask(ResourceNodeVoter::getReaderMask())
        ;

        /** @var BaseTool $tool */
        foreach ($tools as $tool) {
            $toolEntity = new Tool();
            $toolEntity
                ->setName($tool->getName())
                ->setImage($tool->getImage())
                ->setDescription($tool->getName().' - description')
                ->addToolResourceRights($toolResourceRight)
                ->addToolResourceRights($toolResourceRightReader)
            ;
            $manager->persist($toolEntity);
        }
    }

    /**
     * @param Course $course
     *
     * @return Course
     */
    public function addToolsInCourse(Course $course)
    {
        $tools = $this->getTools();

        /** @var BaseTool $tool */
        foreach ($tools as $tool) {
            $toolEntity = new CTool();
            $toolEntity
                ->setCId($course->getId())
                ->setImage($tool->getImage())
                ->setName($tool->getName())
                ->setVisibility(1)
                ->setLink($tool->getLink())
                ->setTarget($tool->getTarget())
                ->setCategory($tool->getCategory());

            $course->addTools($toolEntity);
        }

        return $course;
    }

    /**
     * @param string $name
     *
     * @return BaseTool
     */
    public function getToolFromName($name)
    {
        $tools = $this->getTools();

        if (array_key_exists($name, $tools)) {
            return $tools[$name];
        }

        return false;
    }
}
