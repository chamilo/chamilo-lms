<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

/**
 * Class LtiContentItemType.
 */
abstract class LtiContentItemType
{
    /**
     * LtiContentItemType constructor.
     *
     * @param stdClass $itemData
     */
    abstract public function __construct(stdClass $itemData);

    /**
     * @param stdClass $itemData
     *
     * @throws Exception
     */
    abstract protected function validateItemData(stdClass $itemData);

    /**
     * @param ImsLtiTool $baseTool
     * @param Course     $course
     *
     * @throws Exception
     *
     * @return ImsLtiTool
     */
    abstract protected function createTool(ImsLtiTool $baseTool);

    /**
     * @param ImsLtiTool $baseTool
     * @param Course     $course
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return ImsLtiTool
     */
    public function save(ImsLtiTool $baseTool, Course $course)
    {
        $newTool = $this->createTool($baseTool);
        $newTool->setActiveDeepLinking(false);

        $em = Database::getManager();

        $em->persist($newTool);
        $em->flush();

        ImsLtiPlugin::create()->addCourseTool($course, $newTool);

        return $newTool;
    }
}
