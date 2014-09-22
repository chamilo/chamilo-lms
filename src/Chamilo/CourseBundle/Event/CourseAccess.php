<?php

namespace Chamilo\CourseBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CourseAccess extends Event
{
    protected $user;
    protected $course;

    public function __construct($user, $course)
    {
        $this->user = $user;
        $this->course = $course;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCourse()
    {
        return $this->course;
    }
}
