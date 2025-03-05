<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CoreBundle\Entity\User;

class UserUpdatedHookEvent extends HookEvent
{
    public function getUser(): ?User
    {
        return $this->data['user'] ?? null;
    }
}
