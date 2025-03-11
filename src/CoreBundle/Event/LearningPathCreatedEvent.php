<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CourseBundle\Entity\CLp;

class LearningPathCreatedEvent extends AbstractEvent
{
    public function getLp(): ?CLp
    {
        return $this->data['lp'] ?? null;
    }
}
