<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class LearningPath
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Link extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Link';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'link/link.php';
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
