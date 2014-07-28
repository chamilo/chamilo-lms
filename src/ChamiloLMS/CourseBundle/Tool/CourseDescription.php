<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class CourseDescription
 * @package ChamiloLMS\CourseBundle\Tool
 */
class CourseDescription extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'CourseDescription';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'course_description/index.php';
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
