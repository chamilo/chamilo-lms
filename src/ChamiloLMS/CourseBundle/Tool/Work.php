<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Dropbox
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Work extends BaseTool
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
        return 'work/work.php';
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
