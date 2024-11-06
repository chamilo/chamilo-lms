<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CPeerAssessmentCriteria.
 */
#[ORM\Table(name: 'c_peer_assessment_criteria')]
#[ORM\Entity]
class CPeerAssessmentCriteria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CPeerAssessment::class)]
    #[ORM\JoinColumn(name: 'peer_assessment_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CPeerAssessment $peerAssessment = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $score = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $position = null;


    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeerAssessment(): ?CPeerAssessment
    {
        return $this->peerAssessment;
    }

    public function setPeerAssessment(?CPeerAssessment $peerAssessment): self
    {
        $this->peerAssessment = $peerAssessment;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;

        return $this;
    }
}
