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
     *
     * @throws Exception
     */
    public function __construct(stdClass $itemData)
    {
        $this->validateItemData($itemData);
    }

    /**
     * @param ImsLtiTool $baseTool
     * @param Course     $course
     */
    abstract function save(ImsLtiTool $baseTool, Course $course);

    /**
     * @param stdClass $itemData
     *
     * @throws Exception
     */
    abstract protected function validateItemData(stdClass $itemData);
}
