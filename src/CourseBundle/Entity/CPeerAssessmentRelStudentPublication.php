<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CPeerAssessmentRelStudentPublication.
 */
#[ORM\Table(name: 'c_peer_assessment_rel_student_publication')]
#[ORM\Entity]
class CPeerAssessmentRelStudentPublication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CPeerAssessment::class)]
    #[ORM\JoinColumn(name: 'peer_assessment_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CPeerAssessment $peerAssessment = null;

    #[ORM\ManyToOne(targetEntity: CStudentPublication::class)]
    #[ORM\JoinColumn(name: 'student_publication_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CStudentPublication $studentPublication = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CGroup $group = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    protected ?int $studentPublicationFolderId = null;

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

    public function getStudentPublication(): ?CStudentPublication
    {
        return $this->studentPublication;
    }

    public function setStudentPublication(?CStudentPublication $studentPublication): self
    {
        $this->studentPublication = $studentPublication;

        return $this;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getStudentPublicationFolderId(): ?int
    {
        return $this->studentPublicationFolderId;
    }

    public function setStudentPublicationFolderId(?int $studentPublicationFolderId): self
    {
        $this->studentPublicationFolderId = $studentPublicationFolderId;

        return $this;
    }
}
