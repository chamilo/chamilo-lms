<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle;

/**
 * Class ToolChain
 * @package Chamilo\CourseBundle
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
