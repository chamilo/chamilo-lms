<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CoreBundle\Entity\Portfolio;

class PortfolioItemDeletedHookEvent extends HookEvent
{
    public function getPortfolio(): ?Portfolio
    {
        return $this->data['portfolio'] ?? null;
    }
}
