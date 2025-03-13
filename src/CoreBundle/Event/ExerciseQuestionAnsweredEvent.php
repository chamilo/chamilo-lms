<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Event;

class ExerciseQuestionAnsweredEvent extends AbstractEvent
{
    public function getTrackingExeId(): ?int
    {
        return $this->data['exe_id'] ?? null;
    }

    public function getExerciseId(): ?int
    {
        if (!empty($this->data['exercise']['id'])) {
            return $this->data['exercise']['id'];
        }

        return null;
    }

    public function getExerciseTitle(): ?string
    {
        if (!empty($this->data['exercise']['title'])) {
            return $this->data['exercise']['title'];
        }

        return null;
    }

    public function getQuestionId(): ?int
    {
        if (!empty($this->data['question']['id'])) {
            return $this->data['question']['id'];
        }

        return null;
    }

    public function getQuestionWeigth(): ?float
    {
        if (!empty($this->data['question']['weight'])) {
            return $this->data['question']['weight'];
        }

        return null;
    }
}
