<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\Course;

class CourseCreatedEvent extends AbstractEvent
{
    public function getCourse(): ?Course
    {
        return $this->data['course'] ?? null;
    }
}
