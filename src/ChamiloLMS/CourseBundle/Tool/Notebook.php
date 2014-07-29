<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Notebook
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Notebook extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Notebook';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'notebook/index.php';
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
