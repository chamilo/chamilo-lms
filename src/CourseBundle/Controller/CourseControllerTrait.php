<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * Trait CourseControllerTrait.
 * Implements the functions defined by the CourseControllerInterface.
 */
trait CourseControllerTrait
{
    protected $course;
    protected $session;

    public function setCourse(Course $course)
    {
        $this->course = $course;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    public function hasCourse(): bool
    {
        return null !== $this->course;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->session ? $this->getSession()->getId() : 0;
    }
}
