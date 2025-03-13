<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\User;

class PortfolioItemDownloadedEvent extends AbstractEvent
{
    public function getOwner(): ?User
    {
        return $this->data['owner'] ?? null;
    }
}
