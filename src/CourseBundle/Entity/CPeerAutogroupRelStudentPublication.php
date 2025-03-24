<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents the link between autogroups and peer assessment related to student publications.
 */
#[ORM\Table(name: 'c_peer_autogroup_rel_student_publication')]
#[ORM\Entity]
class CPeerAutogroupRelStudentPublication
{
    use UserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(name: 'peer_autogroup_id', type: 'integer', nullable: true)]
    protected ?int $peerAutogroupId = null;

    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => 0])]
    protected ?bool $vote = null;

    #[ORM\Column(name: 'date_vote', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $dateVote = null;

    #[ORM\ManyToOne(targetEntity: CStudentPublication::class)]
    #[ORM\JoinColumn(name: 'student_publication_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CStudentPublication $studentPublication = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CGroup $group = null;

    #[ORM\Column(name: 'student_publication_parent_id', type: 'integer', nullable: true)]
    protected ?int $studentPublicationParentId = null;

    #[ORM\Column(name: 'student_publication_folder_id', type: 'integer', nullable: true)]
    protected ?int $studentPublicationFolderId = null;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeerAutogroupId(): ?int
    {
        return $this->peerAutogroupId;
    }

    public function setPeerAutogroupId(?int $peerAutogroupId): self
    {
        $this->peerAutogroupId = $peerAutogroupId;

        return $this;
    }

    public function getVote(): ?bool
    {
        return $this->vote;
    }

    public function setVote(?bool $vote): self
    {
        $this->vote = $vote;

        return $this;
    }

    public function getDateVote(): ?DateTimeInterface
    {
        return $this->dateVote;
    }

    public function setDateVote(?DateTimeInterface $dateVote): self
    {
        $this->dateVote = $dateVote;

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

    public function getStudentPublicationParentId(): ?int
    {
        return $this->studentPublicationParentId;
    }

    public function setStudentPublicationParentId(?int $studentPublicationParentId): self
    {
        $this->studentPublicationParentId = $studentPublicationParentId;

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
