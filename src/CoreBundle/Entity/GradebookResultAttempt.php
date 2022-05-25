<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Table(name="gradebook_result_attempt")
 * @ORM\Entity
 */
class GradebookResultAttempt
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(name="score", type="float", nullable=true)
     */
    protected ?float $score = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\GradebookResult")
     * @ORM\JoinColumn(name="result_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected GradebookResult $result;

    public function getId(): int
    {
        return $this->id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getResult(): GradebookResult
    {
        return $this->result;
    }

    public function setResult(GradebookResult $result): self
    {
        $this->result = $result;

        return $this;
    }
}
