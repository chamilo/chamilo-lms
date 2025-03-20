<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\Portfolio;

class PortfolioItemVisibilityChangedEvent extends AbstractEvent
{
    public function getPortfolio(): ?Portfolio
    {
        return $this->data['portfolio'] ?? null;
    }

    public function getRecipientIdList(): array
    {
        return $this->data['recipients'] ?? [];
    }
}
