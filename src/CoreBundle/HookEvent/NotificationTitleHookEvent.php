<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\HookEvent;

class NotificationTitleHookEvent extends HookEvent
{
    public function getTitle(): string
    {
        return $this->data['title'] ?? '';
    }

    public function setTitle(string $title): static
    {
        $this->data['title'] = $title;

        return $this;
    }
}
