<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Interface ToolInterface.
 *
 * @package Chamilo\CourseBundle\Tool
 */
interface ToolInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getLink();

    /**
     * @return string
     */
    public function getTarget();

    /**
     * @return string
     */
    public function getCategory();

    /**
     * @return string
     */
    //public function getName();
}
