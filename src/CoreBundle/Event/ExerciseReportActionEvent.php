<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class ExerciseReportActionEvent extends AbstractEvent
{
    public function addAction(string $linkAction): void
    {
        $this->data['actions'][] = $linkAction;
    }

    public function getActions(): array
    {
        return $this->data['actions'] ?? [];
    }

    public function setQuizId(int $quizId): void
    {
        $this->data['quiz']['id'] = $quizId;
    }

    public function getQuizId(): ?int
    {
        return $this->data['quiz']['id'] ?? null;
    }
}
