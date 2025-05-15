<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class AdminBlockDisplayedEvent extends AbstractEvent
{
    /**
     * @return array<int, array<string, string>>
     */
    public function getItems(string $title): array
    {
        return $this->data[$title]['items'] ?? [];
    }

    public function setItems(string $title, array $items): static
    {
        $currentItems = $this->data[$title]['items'] ?? [];

        $this->data[$title]['items'] = [...$currentItems, ...$items];

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getBlockNames(): array
    {
        return array_keys($this->data);
    }
}
