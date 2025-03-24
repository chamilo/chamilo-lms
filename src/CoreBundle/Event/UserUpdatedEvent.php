<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\User;

class UserUpdatedEvent extends AbstractEvent
{
    public function getUser(): ?User
    {
        return $this->data['user'] ?? null;
    }
}
