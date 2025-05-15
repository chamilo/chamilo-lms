<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class DocumentActionEvent extends AbstractEvent
{
    public function getAction(): array
    {
        return $this->data['action'] ?? [];
    }
}
