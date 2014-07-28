<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class LearningPath
 * @package ChamiloLMS\CourseBundle\Tool
 */
class LearningPath extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'LearningPath';
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
