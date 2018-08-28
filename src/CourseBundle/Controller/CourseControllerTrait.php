<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * Trait CourseControllerTrait.
 * Implements the functions defined by the CourseControllerInterface.
 *
 * @package Chamilo\CourseBundle\Controller
 */
trait CourseControllerTrait
{
    protected $course;
    protected $session;

    /**
     * @param Course $course
     *
     * @return mixed
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;
    }

    /**
     * @param Session $session
     *
     * @return mixed
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
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
