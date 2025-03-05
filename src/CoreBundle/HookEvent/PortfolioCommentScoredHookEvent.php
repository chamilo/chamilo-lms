<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

use Chamilo\CoreBundle\Entity\PortfolioComment;

class PortfolioCommentScoredHookEvent extends HookEvent
{
    public function getComment(): ?PortfolioComment
    {
        return $this->data['comment'] ?? null;
    }
}
