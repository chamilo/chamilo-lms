<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\Course;

/**
 * Trait CourseTrait.
 */
trait CourseTrait
{
    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @param Course $course
     *
     * @return $this
     */
    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }
}
