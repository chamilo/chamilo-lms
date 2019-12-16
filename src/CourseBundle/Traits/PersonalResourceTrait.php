<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Traits;

/**
 * Trait ShowCourseResourcesInSessionTrait.
 */
trait PersonalResourceTrait
{
    protected $loadPersonalResources = true;

    public function loadPersonalResources(): bool
    {
        return $this->loadPersonalResources;
    }
}
