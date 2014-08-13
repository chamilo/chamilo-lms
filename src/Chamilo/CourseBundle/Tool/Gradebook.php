<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Gradebook
 * @package Chamilo\CourseBundle\Tool
 */
class Gradebook extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Gradebook';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'gradebook/index.php';
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
