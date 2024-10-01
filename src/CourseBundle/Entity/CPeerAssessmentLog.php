<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CPeerAssessmentLog.
 */
#[ORM\Table(name: 'c_peer_assessment_log')]
#[ORM\Entity]
class CPeerAssessmentLog
{
    use UserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CPeerAssessment::class)]
    #[ORM\JoinColumn(name: 'peer_assessment_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?CPeerAssessment $peerAssessment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTime $date = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $description = null;

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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): self
    {
        $this->date = $date;

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
}
