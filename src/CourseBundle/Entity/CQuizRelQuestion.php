<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuizRelQuestion.
 */
#[ORM\Table(name: 'c_quiz_rel_question')]
#[ORM\Index(name: 'question', columns: ['question_id'])]
#[ORM\Index(name: 'exercise', columns: ['quiz_id'])]
#[ORM\Entity]
class CQuizRelQuestion
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'question_order', type: 'integer', nullable: false)]
    protected int $questionOrder;

    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: CQuizQuestion::class, cascade: ['persist'], inversedBy: 'relQuizzes')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CQuizQuestion $question;

    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: CQuiz::class, cascade: ['persist'], inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CQuiz $quiz;

    #[ORM\Column(name: 'destination', type: 'text', nullable: true)]
    protected ?string $destination = null;

    public function setQuestionOrder(int $questionOrder): self
    {
        $this->questionOrder = $questionOrder;

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

    /**
     * Get questionOrder.
     *
     * @return int
     */
    public function getQuestionOrder()
    {
        return $this->questionOrder;
    }

    public function getQuiz(): CQuiz
    {
        return $this->quiz;
    }

    public function setQuiz(CQuiz $quiz): self
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }
}
