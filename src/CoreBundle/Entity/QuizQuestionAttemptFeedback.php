<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CQuizQuestion;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_quiz_question_attempt_feedback')]
#[ORM\Index(columns: ['attempt_id'], name: 'IDX_QQAF_ATTEMPT')]
#[ORM\Index(columns: ['question_id'], name: 'IDX_QQAF_QUESTION')]
#[ORM\Entity]
class QuizQuestionAttemptFeedback
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TrackEExercise::class)]
    #[ORM\JoinColumn(name: 'attempt_id', referencedColumnName: 'exe_id', nullable: false, onDelete: 'CASCADE')]
    private TrackEExercise $attempt;

    #[ORM\ManyToOne(targetEntity: CQuizQuestion::class)]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    private CQuizQuestion $question;

    #[ORM\Column(name: 'feedback', type: 'text', nullable: false)]
    private string $feedback = '';

    #[ORM\Column(name: 'feedback_time', type: 'datetime', nullable: false)]
    private DateTime $feedbackTime;

    public function __construct()
    {
        $this->feedbackTime = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttempt(): TrackEExercise
    {
        return $this->attempt;
    }

    public function setAttempt(TrackEExercise $attempt): self
    {
        $this->attempt = $attempt;

        return $this;
    }

    public function getQuestion(): CQuizQuestion
    {
        return $this->question;
    }

    public function setQuestion(CQuizQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getFeedback(): string
    {
        return $this->feedback;
    }

    public function setFeedback(string $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }

    public function getFeedbackTime(): DateTime
    {
        return $this->feedbackTime;
    }

    public function setFeedbackTime(DateTime $feedbackTime): self
    {
        $this->feedbackTime = $feedbackTime;

        return $this;
    }
}
