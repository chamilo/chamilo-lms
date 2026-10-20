<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Immutable history of learner answer changes within an exercise attempt.
 */
#[ORM\Entity]
#[ORM\Table(name: 'track_e_attempt_log')]
#[ORM\Index(columns: ['exe_id', 'question_id'], name: 'idx_track_e_attempt_log_exe_question')]
class TrackEAttemptLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TrackEExercise::class)]
    #[ORM\JoinColumn(name: 'exe_id', referencedColumnName: 'exe_id', nullable: false, onDelete: 'CASCADE')]
    private TrackEExercise $trackExercise;

    #[ORM\Column(name: 'question_id', type: 'integer')]
    private int $questionId;

    #[ORM\Column(name: 'event_type', type: 'string', length: 16)]
    private string $eventType;

    #[ORM\Column(name: 'answer', type: 'text')]
    private string $answer;

    #[ORM\Column(name: 'seconds_spent', type: 'integer')]
    private int $secondsSpent = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrackExercise(): TrackEExercise
    {
        return $this->trackExercise;
    }

    public function setTrackExercise(TrackEExercise $trackExercise): self
    {
        $this->trackExercise = $trackExercise;

        return $this;
    }

    public function getQuestionId(): int
    {
        return $this->questionId;
    }

    public function setQuestionId(int $questionId): self
    {
        $this->questionId = $questionId;

        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getSecondsSpent(): int
    {
        return $this->secondsSpent;
    }

    public function setSecondsSpent(int $secondsSpent): self
    {
        $this->secondsSpent = $secondsSpent;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
