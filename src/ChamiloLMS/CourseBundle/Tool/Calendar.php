<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Calendar
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Calendar extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Calendar';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'calendar/agenda.php';
    }

    public function getTarget()
    {
        return '_self';
    }

    public function getCategory()
    {
        return 'authoring';
    }
}
