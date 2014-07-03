<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Calendar
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Document extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Document';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'document/document.php';
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
