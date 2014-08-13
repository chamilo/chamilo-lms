<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Assignment
 * @package Chamilo\CourseBundle\Tool
 */
class Assignment extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Assignment';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'work/work.php';
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
