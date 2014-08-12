<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class CourseDescription
 * @package ChamiloLMS\CourseBundle\Tool
 */
class Chat extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chat';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'chat/chat.php';
    }

    public function getCategory()
    {
        return 'interaction';
    }
}
