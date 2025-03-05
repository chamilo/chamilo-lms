<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CoreBundle\Entity\User;

class PortfolioItemDownloadedHookEvent extends HookEvent
{
    public function getOwner(): ?User
    {
        return $this->data['owner'] ?? null;
    }
}
