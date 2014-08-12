<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Attendance
 * @package ChamiloLMS\CourseBundle\Tool
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
