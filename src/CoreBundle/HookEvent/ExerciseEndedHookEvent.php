<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class ExerciseEndedHookEvent extends HookEvent
{
    public function getTrackingExeId(): ?int
    {
        return $this->data['exe_id'] ?? null;
    }
}
