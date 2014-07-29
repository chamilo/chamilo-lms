<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Work
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Work extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Work';
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
