<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Chamilo\CoreBundle\Entity\Usergroup;

/**
 * CPeerAssessmentCorrection.
 */
#[ORM\Table(name: 'c_peer_assessment_correction')]
#[ORM\Entity]
class CPeerAssessmentCorrection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CPeerAssessment::class)]
    #[ORM\JoinColumn(name: 'peer_assessment_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CPeerAssessment $peerAssessment = null;

    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(name: 'student_group_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Usergroup $studentGroup = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $examinerId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $examinerGroupId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $totalScore = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $maximumScore = 0;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $delivered = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $examinerFolderId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $examinerDocumentId = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $completed = false;


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

    public function getStudentGroup(): ?Usergroup
    {
        return $this->studentGroup;
    }

    public function setStudentGroup(?Usergroup $studentGroup): self
    {
        $this->studentGroup = $studentGroup;

        return $this;
    }

    public function getExaminerId(): ?int
    {
        return $this->examinerId;
    }

    public function setExaminerId(?int $examinerId): self
    {
        $this->examinerId = $examinerId;

        return $this;
    }

    public function getExaminerGroupId(): ?int
    {
        return $this->examinerGroupId;
    }

    public function setExaminerGroupId(?int $examinerGroupId): self
    {
        $this->examinerGroupId = $examinerGroupId;

        return $this;
    }

    public function getTotalScore(): ?int
    {
        return $this->totalScore;
    }

    public function setTotalScore(?int $totalScore): self
    {
        $this->totalScore = $totalScore;

        return $this;
    }

    public function getMaximumScore(): ?int
    {
        return $this->maximumScore;
    }

    public function setMaximumScore(?int $maximumScore): self
    {
        $this->maximumScore = $maximumScore;

        return $this;
    }

    public function getDelivered(): ?bool
    {
        return $this->delivered;
    }

    public function setDelivered(?bool $delivered): self
    {
        $this->delivered = $delivered;

        return $this;
    }

    public function getExaminerFolderId(): ?int
    {
        return $this->examinerFolderId;
    }

    public function setExaminerFolderId(?int $examinerFolderId): self
    {
        $this->examinerFolderId = $examinerFolderId;

        return $this;
    }

    public function getExaminerDocumentId(): ?int
    {
        return $this->examinerDocumentId;
    }

    public function setExaminerDocumentId(?int $examinerDocumentId): self
    {
        $this->examinerDocumentId = $examinerDocumentId;

        return $this;
    }

    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(?bool $completed): self
    {
        $this->completed = $completed;

        return $this;
    }
}
