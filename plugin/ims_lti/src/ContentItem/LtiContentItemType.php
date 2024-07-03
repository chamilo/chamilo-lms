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
     * @throws Exception
     */
    public function __construct(stdClass $itemData)
    {
        $this->validateItemData($itemData);
    }

    abstract public function save(ImsLtiTool $baseTool, Course $course);

    /**
     * @throws Exception
     */
    abstract protected function validateItemData(stdClass $itemData);
}
