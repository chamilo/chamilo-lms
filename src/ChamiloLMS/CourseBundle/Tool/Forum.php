<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Forum
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Forum extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Forum';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'forum/index.php';
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
