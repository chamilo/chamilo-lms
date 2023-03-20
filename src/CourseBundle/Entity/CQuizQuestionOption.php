<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuizQuestionOption.
 *
 * @ORM\Table(
 *     name="c_quiz_question_option",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
class CQuizQuestionOption
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected int $position;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestion", cascade={"persist"}, inversedBy="options")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    #[Assert\NotBlank]
    protected CQuizQuestion $question;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
