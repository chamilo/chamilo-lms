<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Agenda
 * @package Chamilo\CourseBundle\Tool
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
