<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\Course;

/**
 * Dispatched as TYPE_PRE, before the course row is removed, so listeners can clean
 * up what references it: rows whose foreign key would block the deletion, and rows
 * a cascade would drop before the listener could read them.
 */
class CourseDeletedEvent extends AbstractEvent
{
    public function getCourse(): ?Course
    {
        return $this->data['course'] ?? null;
    }

    public function getCourseId(): ?int
    {
        return $this->getCourse()?->getId();
    }
}
