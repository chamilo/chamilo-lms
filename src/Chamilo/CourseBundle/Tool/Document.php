<?php

namespace Chamilo\CourseBundle\Tool;

/**
 * Class Document
 * @package Chamilo\CourseBundle\Tool
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
