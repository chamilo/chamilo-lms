<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class CheckLoginCredentialsEvent extends AbstractEvent
{
    /**
     * @return array<string, mixed>
     */
    public function getUser(): array
    {
        return $this->data['user'] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function getCredentials(): array
    {
        return $this->data['credentials'] ?? [];
    }
}
