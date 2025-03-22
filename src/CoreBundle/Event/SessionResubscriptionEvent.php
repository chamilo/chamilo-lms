<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class SessionResubscriptionEvent extends AbstractEvent
{
    public function getSessionId(): ?int
    {
        return $this->data['session_id'] ?? null;
    }
}
