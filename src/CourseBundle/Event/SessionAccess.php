<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Event;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class SessionAccess extends Event
{
    protected User $user;
    protected Course $course;
    protected Session $session;

    public function __construct(User $user, Course $course, Session $session)
    {
        $this->user = $user;
        $this->course = $course;
        $this->session = $session;
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

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}
