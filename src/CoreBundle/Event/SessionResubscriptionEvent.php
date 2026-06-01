<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class SessionResubscriptionEvent extends AbstractEvent
{
    public function getSessionId(): ?int
    {
        return isset($this->data['session_id']) ? (int) $this->data['session_id'] : null;
    }

    public function getUserId(): ?int
    {
        return isset($this->data['user_id']) ? (int) $this->data['user_id'] : null;
    }
}
