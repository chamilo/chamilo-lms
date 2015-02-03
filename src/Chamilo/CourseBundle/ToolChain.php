<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Tool\BaseTool;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ToolChain
 * @package Chamilo\CourseBundle
 */
class ToolChain
{
    protected $tools;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->tools = array();
    }

    /**
     * @param $tool
     */
    public function addTool($tool)
    {
        $this->tools[] = $tool;
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
                ->setLink($tool->getLink())
                ->setTarget($tool->getTarget())
                ->setCategory($tool->getCategory());

            $course->addTools($toolEntity);
        }

        return $course;
    }
}
