<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Traits;

/**
 * Trait ShowCourseResourcesInSessionTrait.
 */
trait ShowCourseResourcesInSessionTrait
{
    protected $loadCourseResourcesInSession = true;

    public function isLoadCourseResourcesInSession(): bool
    {
        return $this->loadCourseResourcesInSession;
    }
}
