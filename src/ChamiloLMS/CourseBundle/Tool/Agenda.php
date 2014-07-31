<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Agenda
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Agenda extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'calendar/agenda.php';
    }


    public function getCategory()
    {
        return 'authoring';
    }
}
