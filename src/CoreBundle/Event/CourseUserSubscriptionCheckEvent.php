<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

final class CourseUserSubscriptionCheckEvent extends AbstractEvent
{
    public function getCourseId(): int
    {
        return (int) ($this->data['course_id'] ?? 0);
    }

    /**
     * @return int[]
     */
    public function getUserIds(): array
    {
        $userIds = $this->data['user_ids'] ?? [];

        if (!\is_array($userIds)) {
            $userIds = [$userIds];
        }

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        return array_values(array_filter(
            $userIds,
            static fn (int $userId): bool => $userId > 0
        ));
    }

    public function getStatus(): int
    {
        return (int) ($this->data['status'] ?? 0);
    }

    public function getSessionId(): int
    {
        return (int) ($this->data['session_id'] ?? 0);
    }

    public function isAllowed(): bool
    {
        return false !== ($this->data['allowed'] ?? true);
    }

    public function deny(string $message): void
    {
        $this->data['allowed'] = false;
        $this->data['message'] = $message;
    }

    public function getMessage(): string
    {
        return (string) ($this->data['message'] ?? '');
    }
}
