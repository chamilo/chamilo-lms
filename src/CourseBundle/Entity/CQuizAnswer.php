<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuizAnswer.
 */
#[ORM\Table(name: 'c_quiz_answer')]
#[ORM\Index(name: 'idx_cqa_q', columns: ['question_id'])]
#[ORM\Entity]
class CQuizAnswer
{
    #[ORM\Column(name: 'iid', type: 'integer', options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CourseBundle\Entity\CQuizQuestion::class, inversedBy: 'answers', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CQuizQuestion $question;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'answer', type: 'text', nullable: false)]
    protected string $answer;

    #[ORM\Column(name: 'correct', type: 'integer', nullable: true)]
    protected ?int $correct;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment;

    #[ORM\Column(name: 'ponderation', type: 'float', precision: 6, scale: 2, nullable: false, options: ['default' => 0])]
    protected float $ponderation;

    #[ORM\Column(name: 'position', type: 'integer', nullable: false)]
    protected int $position;

    #[ORM\Column(name: 'hotspot_coordinates', type: 'text', nullable: true)]
    protected ?string $hotspotCoordinates;

    #[ORM\Column(name: 'hotspot_type', type: 'string', length: 40, nullable: true)]
    protected ?string $hotspotType;

    #[ORM\Column(name: 'destination', type: 'text', nullable: true)]
    protected ?string $destination;

    #[ORM\Column(name: 'answer_code', type: 'string', length: 10, nullable: true)]
    protected ?string $answerCode;

    public function __construct()
    {
        $this->answer = '';
        $this->correct = null;
        $this->comment = null;
        $this->ponderation = 0.0;
        $this->hotspotCoordinates = null;
        $this->hotspotType = null;
        $this->destination = null;
        $this->answerCode = null;
    }

    public function setAnswer(string $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setCorrect(int $correct): self
    {
        $this->correct = $correct;

        return $this;
    }

    
    public function getCorrect(): ?int
    {
        return $this->correct;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setPonderation(string|float $weight): self
    {
        $this->ponderation = empty($weight) ? 0.0 : (float) $weight;

        return $this;
    }

    /**
     * Get weight.
     */
    public function getPonderation(): float
    {
        return $this->ponderation;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    
    public function getPosition(): int
    {
        return $this->position;
    }

    public function setHotspotCoordinates(?string $hotspotCoordinates): self
    {
        $this->hotspotCoordinates = $hotspotCoordinates;

        return $this;
    }

    
    public function getHotspotCoordinates(): ?string
    {
        return $this->hotspotCoordinates;
    }

    public function setHotspotType(?string $hotspotType): self
    {
        $this->hotspotType = $hotspotType;

        return $this;
    }

    
    public function getHotspotType(): ?string
    {
        return $this->hotspotType;
    }

    public function setDestination(?string $destination): self
    {
        $this->destination = empty($destination) ? null : $destination;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setAnswerCode(string $answerCode): self
    {
        $this->answerCode = $answerCode;

        return $this;
    }

    /**
     * Get answerCode.
     *
     * @return string
     */
    public function getAnswerCode()
    {
        return $this->answerCode;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
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
}
