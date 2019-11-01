<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Traits;

/**
 * Trait SessionCourseResourcesTrait.
 */
trait SessionCourseResourcesTrait
{
    protected $loadBaseCourseResourcesFromSession = true;

    /**
     * @return bool
     */
    public function isLoadBaseCourseResourcesFromSession(): bool
    {
        return $this->loadBaseCourseResourcesFromSession;
    }
}
