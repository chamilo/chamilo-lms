<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Dropbox
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Group extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Group';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'group/group.php';
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
