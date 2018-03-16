<?php

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * Interface ToolInterface
 * This functions are loaded in the CourseListener.php.
 *
 * @package Chamilo\CourseBundle\Controller
 */
interface ToolInterface
{
    /**
     * @param Course $course
     *
     * @return mixed
     */
    public function setCourse(Course $course);

    /**
     * @param Session $session
     *
     * @return mixed
     */
    public function setSession(Session $session);

    /**
     * @return Course
     */
    public function getCourse();

    /**
     * @return Session
     */
    public function getSession();
}
