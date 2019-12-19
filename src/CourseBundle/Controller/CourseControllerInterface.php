<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * CourseControllerInterface.
 * This interface provides getters and setters to a controller.
 * This functions are loaded when the CourseListener.php fires when a c_id/cidReq/ or courses/XXX/ parameter and
 * the controller implements this interface. See the ResourceController class as an example.
 * is loaded in the URL.
 */
interface CourseControllerInterface
{
    public function setCourse(Course $course);

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
