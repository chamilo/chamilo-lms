<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * CourseControllerInterface.
 * This interface provides getters and setters to a controller.
 * These functions are loaded when the CourseListener.php fires when a c_id/cidReq/ or courses/XXX/ parameter and
 * the controller implements this interface. See the ResourceController class as an example.
 * is loaded in the URL.
 */
interface CourseControllerInterface
{
    public function setCourse(Course $course): void;

    public function setSession(Session $session = null): void;

    public function getCourse(): ?Course;

    public function getSession(): ?Session;
}
