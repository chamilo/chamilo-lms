<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class LearningPathEndedEvent extends AbstractEvent
{
    public function getLpViewId(): array
    {
        return $this->data['lp_view_id'] ?? [];
    }
}
