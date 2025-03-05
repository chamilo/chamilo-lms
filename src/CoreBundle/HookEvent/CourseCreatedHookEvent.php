<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class CourseCreatedHookEvent extends HookEvent
{
    public function getCourseInfo(): array
    {
        return $this->data['course_info'] ?? [];
    }
}
