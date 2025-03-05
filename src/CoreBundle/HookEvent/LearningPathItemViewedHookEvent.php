<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class LearningPathItemViewedHookEvent extends HookEvent
{
    public function getItemViewId(): ?int
    {
        return $this->data['item_view_id'] ?? null;
    }
}
