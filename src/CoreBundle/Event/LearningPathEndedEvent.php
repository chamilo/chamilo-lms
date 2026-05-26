<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Event;

class LearningPathEndedEvent extends AbstractEvent
{
    public function getLpViewId(): int
    {
        return (int) ($this->data['lp_view_id'] ?? 0);
    }
}
