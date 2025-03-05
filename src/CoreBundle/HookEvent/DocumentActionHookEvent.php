<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class DocumentActionHookEvent extends HookEvent
{
    public function getAction(): array
    {
        return $this->data['action'] ?? [];
    }
}
