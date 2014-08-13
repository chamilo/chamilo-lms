<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Group
 * @package Chamilo\CourseBundle\Tool
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
        return 'interaction';
    }
}
