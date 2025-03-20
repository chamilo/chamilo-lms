<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class MyStudentsLpTrackingEvent extends AbstractEvent
{
    public function getHeaders(): array
    {
        return $this->data['headers'] ?? [];
    }

    public function addHeader(string $title, array $attrs): static
    {
        $headers = $this->getHeaders();

        $this->data['headers'] = [
            ...$headers,
            [
                'title' => $title,
                'attrs' => $attrs,
            ],
        ];

        return $this;
    }

    public function getContents(): array
    {
        return $this->data['contents'] ?? [];
    }

    public function addContent(string $value, array $attrs): static
    {
        $contents = $this->getContents();

        $this->data['contents'] = [
            ...$contents,
            [
                'value' => $value,
                'attrs' => $attrs,
            ],
        ];

        return $this;
    }

    public function getLpId(): ?int
    {
        return $this->data['lp_id'] ?? null;
    }

    public function getStudentId(): ?int
    {
        return $this->data['student_id'] ?? null;
    }
}
