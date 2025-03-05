<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class SessionResubscriptionHookEvent extends HookEvent
{
    public function getSessionId(): ?int
    {
        return $this->data['session_id'] ?? null;
    }
}
