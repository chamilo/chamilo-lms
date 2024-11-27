<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CPeerAssessmentCorrectionCriteria.
 */
#[ORM\Table(name: 'c_peer_assessment_correction_criteria')]
#[ORM\Entity]
class CPeerAssessmentCorrectionCriteria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CPeerAssessmentCorrection::class)]
    #[ORM\JoinColumn(name: 'peer_assessment_correction_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CPeerAssessmentCorrection $peerAssessmentCorrection = null;

    #[ORM\ManyToOne(targetEntity: CPeerAssessmentCriteria::class)]
    #[ORM\JoinColumn(name: 'peer_assessment_criteria_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CPeerAssessmentCriteria $peerAssessmentCriteria = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $comment = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $score = null;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeerAssessmentCorrection(): ?CPeerAssessmentCorrection
    {
        return $this->peerAssessmentCorrection;
    }

    public function setPeerAssessmentCorrection(?CPeerAssessmentCorrection $peerAssessmentCorrection): self
    {
        $this->peerAssessmentCorrection = $peerAssessmentCorrection;

        return $this;
    }

    public function getPeerAssessmentCriteria(): ?CPeerAssessmentCriteria
    {
        return $this->peerAssessmentCriteria;
    }

    public function setPeerAssessmentCriteria(?CPeerAssessmentCriteria $peerAssessmentCriteria): self
    {
        $this->peerAssessmentCriteria = $peerAssessmentCriteria;

        return $this;
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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): self
    {
        $this->score = $score;

        return $this;
    }
}
