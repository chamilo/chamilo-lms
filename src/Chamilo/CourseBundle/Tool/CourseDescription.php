<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class CourseDescription
 * @package Chamilo\CourseBundle\Tool
 */
class CourseDescription extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'course_description';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'course_description/index.php';
    }

    public function getCategory()
    {
        return 'authoring';
    }
}
