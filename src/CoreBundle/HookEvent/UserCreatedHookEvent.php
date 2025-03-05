<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CoreBundle\Entity\User;

class UserCreatedHookEvent extends HookEvent
{
    public function getUser(): ?User
    {
        return $this->data['return'] ?? null;
    }

    public function getOriginalPassword(): ?string
    {
        return $this->data['originalPassword'] ?? null;
    }
}
