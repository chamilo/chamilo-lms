<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle;

/**
 * Class ToolChain
 * @package ChamiloLMS\CourseBundle
 */
class ToolChain
{
    protected $tools;

    /**
     *
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
}
