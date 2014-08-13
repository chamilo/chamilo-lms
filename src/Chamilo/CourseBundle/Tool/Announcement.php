<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Announcement
 * @package Chamilo\CourseBundle\Tool
 */
class Announcement extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'announcement';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'announcements/announcements.php';
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
