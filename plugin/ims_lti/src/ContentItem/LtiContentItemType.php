<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Doctrine\ORM\OptimisticLockException;

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
     * @param ImsLtiTool $baseTool
     * @param Course     $course
     *
     * @throws OptimisticLockException
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

    /**
     * @param ImsLtiTool $baseTool
     * @param Course     $course
     *
     * @return ImsLtiTool
     * @throws Exception
     *
     */
    abstract protected function createTool(ImsLtiTool $baseTool);

    /**
     * @param stdClass $itemData
     *
     * @throws Exception
     */
    abstract protected function validateItemData(stdClass $itemData);
}
