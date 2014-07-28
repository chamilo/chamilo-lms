<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Exercise
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Exercise extends BaseTool
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
        return 'exercice/exercice.php';
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
