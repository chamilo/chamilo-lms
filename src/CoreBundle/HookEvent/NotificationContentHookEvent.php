<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class NotificationContentHookEvent extends HookEvent
{
    public function getContent(): string
    {
        return $this->data['content'] ?? '';
    }

    public function setContent(string $content): static
    {
        $this->data['content'] = $content;

        return $this;
    }
}
