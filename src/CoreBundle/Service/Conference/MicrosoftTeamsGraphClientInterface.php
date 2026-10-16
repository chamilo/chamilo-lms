<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use DateTimeInterface;

interface MicrosoftTeamsGraphClientInterface
{
    public function isConfigured(): bool;

    /**
     * @return array{meetingId: string, joinUrl: string}
     */
    public function createOnlineMeeting(
        string $organizer,
        string $subject,
        DateTimeInterface $start,
        DateTimeInterface $end,
    ): array;

    public function updateOnlineMeeting(
        string $organizer,
        string $meetingId,
        string $subject,
        DateTimeInterface $start,
        DateTimeInterface $end,
    ): void;

    public function deleteOnlineMeeting(string $organizer, string $meetingId): void;
}
