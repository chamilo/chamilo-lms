<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\User;

class UserCreatedEvent extends AbstractEvent
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
