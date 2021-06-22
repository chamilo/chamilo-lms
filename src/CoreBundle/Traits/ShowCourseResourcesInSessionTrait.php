<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

/**
 * Trait ShowCourseResourcesInSessionTrait.
 */
trait ShowCourseResourcesInSessionTrait
{
    protected bool $loadCourseResourcesInSession = true;

    public function isLoadCourseResourcesInSession(): bool
    {
        return $this->loadCourseResourcesInSession;
    }
}
