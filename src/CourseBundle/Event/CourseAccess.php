<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Event;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CourseAccess.
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
