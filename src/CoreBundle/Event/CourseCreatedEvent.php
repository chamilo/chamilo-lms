<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class CourseCreatedEvent extends AbstractEvent
{
    public function getCourseInfo(): array
    {
        return $this->data['course_info'] ?? [];
    }
}
