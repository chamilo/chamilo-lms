<?php

namespace Chamilo\CourseBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class SessionAccess extends Event
{
    protected $user;
    protected $course;
    protected $session;

    public function __construct($user, $course, $session)
    {
        $this->user = $user;
        $this->course = $course;
        $this->session = $session;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCourse()
    {
        return $this->course;
    }

    public function getSession()
    {
        return $this->session;
    }
}
