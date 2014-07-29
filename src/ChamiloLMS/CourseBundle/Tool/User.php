<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Class User
 * @package ChamiloLMS\CourseBundle\Tool
 */
class User extends BaseTool
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'User';
    }

    /**
     * {@inheritdoc}
     */
    public function getLink()
    {
        return 'user/user.php';
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
