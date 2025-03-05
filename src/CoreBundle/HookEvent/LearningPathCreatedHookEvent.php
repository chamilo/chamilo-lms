<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CourseBundle\Entity\CLp;

class LearningPathCreatedHookEvent extends HookEvent
{
    public function getLp(): ?CLp
    {
        return $this->data['lp'] ?? null;
    }
}
