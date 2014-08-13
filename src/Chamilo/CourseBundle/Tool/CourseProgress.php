<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class CourseDescription
 * @package Chamilo\CourseBundle\Tool
 */
class CourseProgress extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'course_progress';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'course_progress/index.php';
    }

    public function getCategory()
    {
        return 'authoring';
    }
}
