<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

use Chamilo\CourseBundle\Entity\CDocument;

class DocumentItemViewEvent extends AbstractEvent
{
    public function getDocument(): ?CDocument
    {
        return $this->data['document'] ?? null;
    }

    public function addLink(string $link): static
    {
        $this->data['links'][] = $link;

        return $this;
    }

    public function getLinks(): array
    {
        return $this->data['links'] ?? [];
    }
}
