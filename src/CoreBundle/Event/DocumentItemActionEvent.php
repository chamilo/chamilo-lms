<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class DocumentItemActionEvent extends AbstractEvent
{
    public function getAction(): array
    {
        return $this->data['action'] ?? [];
    }

    public function getDocument(): ?array
    {
        return $this->data['document'] ?? null;
    }
}
