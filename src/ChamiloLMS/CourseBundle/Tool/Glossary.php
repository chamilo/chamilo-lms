<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class Glossary
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Glossary extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Glossary';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'glossary/index.php';
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
