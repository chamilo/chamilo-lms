<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Conference;

use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeeting;
use Chamilo\CoreBundle\Entity\ConferenceMeeting;
use Chamilo\CoreBundle\Entity\User;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use const DATE_ATOM;

final readonly class TeamsMeetingResponseBuilder
{
    public function __construct(
        private TeamsMeetingAccessHelper $accessHelper
    ) {}

    public function build(ConferenceMeeting $meeting): TeamsMeeting
    {
        $result = new TeamsMeeting();
        $result->id = $meeting->getId();
        $result->scope = $this->accessHelper->detectScope($meeting);
        $result->title = $meeting->getTitle();
        $result->startAt = $meeting->getStartAt()?->format(DATE_ATOM);
        $result->endAt = $meeting->getEndAt()?->format(DATE_ATOM);
        // Do not expose the raw Microsoft Teams URL to API consumers. All user-facing
        // joins go through the protected Chamilo route, which re-checks access first.
        $result->joinUrl = null;
        $result->status = $this->resolveStatus($meeting);
        $result->canJoin = $this->canJoin($meeting, $result->status);
        $result->canManage = $this->accessHelper->canManageMeeting($meeting)
            && \in_array($result->status, ['upcoming', 'in_progress'], true);
        $result->courseId = $meeting->getCourse()?->getId();
        $result->sessionId = $meeting->getSession()?->getId();
        $result->groupId = $meeting->getGroup()?->getIid();
        $result->createdAt = $meeting->getCreatedAt()->format(DATE_ATOM);
        $result->calendarId = $meeting->getCalendarId();

        $owner = $meeting->getUser();
        $result->organizerName = $owner instanceof User
            ? $owner->getFullName()
            : (trim((string) $meeting->getAccountEmail()) ?: null);

        return $result;
    }

    private function resolveStatus(ConferenceMeeting $meeting): string
    {
        if (!$meeting->isOpen()) {
            return 'cancelled';
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startAt = $meeting->getStartAt();
        $endAt = $meeting->getEndAt();

        if (null !== $endAt && $endAt < $now) {
            return 'past';
        }

        if (null !== $startAt && $startAt <= $now && (null === $endAt || $endAt >= $now)) {
            return 'in_progress';
        }

        return 'upcoming';
    }

    private function canJoin(ConferenceMeeting $meeting, string $status): bool
    {
        if (!\in_array($status, ['upcoming', 'in_progress'], true)) {
            return false;
        }

        try {
            $this->accessHelper->getSafeJoinUrl($meeting);

            return true;
        } catch (ConflictHttpException) {
            return false;
        }
    }
}
