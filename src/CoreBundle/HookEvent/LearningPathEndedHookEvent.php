<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class LearningPathEndedHookEvent extends HookEvent
{
    public function getLpViewId(): array
    {
        return $this->data['lp_view_id'] ?? [];
    }
}
