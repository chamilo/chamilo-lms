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

    public function getBuyCoursesServiceSaleId(): ?int
    {
        $value = $this->data['buycourses_service_sale_id'] ?? null;

        if (null === $value || '' === $value) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
