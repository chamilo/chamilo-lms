<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Event;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class SessionAccess
 * @package Chamilo\CourseBundle\Event
 */
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
