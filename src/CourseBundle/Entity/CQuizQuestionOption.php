<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuizQuestionOption.
 */
#[ORM\Table(name: 'c_quiz_question_option')]
#[ORM\Entity]
class CQuizQuestionOption
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'position', type: 'integer', nullable: false)]
    protected int $position;

    #[Assert\NotBlank]
    #[ORM\ManyToOne(targetEntity: CQuizQuestion::class, cascade: ['persist'], inversedBy: 'options')]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected CQuizQuestion $question;

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
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
