<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use nusoap_server;

class WSRegistrationEvent extends AbstractEvent
{
    public function getServer(): ?nusoap_server
    {
        return $this->data['server'] ?? null;
    }

    public function setServer(nusoap_server $server): static
    {
        $this->data['server'] = $server;

        return $this;
    }
}
