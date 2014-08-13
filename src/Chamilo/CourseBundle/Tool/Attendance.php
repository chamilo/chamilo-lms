<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Attendance
 * @package Chamilo\CourseBundle\Tool
 */
class Attendance extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'attendance/index.php';
    }


    public function getCategory()
    {
        return 'authoring';
    }
}
