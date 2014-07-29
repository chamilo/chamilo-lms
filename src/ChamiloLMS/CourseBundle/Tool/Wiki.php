<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Wiki
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Wiki extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Wiki';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'wiki/index.php';
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
