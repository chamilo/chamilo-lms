<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CoreBundle\Entity\PortfolioComment;

class PortfolioItemCommentedEvent extends AbstractEvent
{
    public function getComment(): ?PortfolioComment
    {
        return $this->data['comment'] ?? null;
    }
}
