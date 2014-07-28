<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Announcements
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Notebook extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Exercise';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'notebook/index.php';
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
