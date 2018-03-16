<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Event;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CourseAccess.
 *
 * @package Chamilo\CourseBundle\Event
 */
class CourseAccess extends Event
{
    protected $user;
    protected $course;

    /**
     * @param $user
     * @param $course
     */
    public function __construct($user, $course)
    {
        $this->user = $user;
        $this->course = $course;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }
}
