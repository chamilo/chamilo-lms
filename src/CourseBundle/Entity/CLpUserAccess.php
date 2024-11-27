<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * C_lp_user_access
 * Represents the access of users to learning paths (LP).
 */
#[ORM\Table(name: 'c_lp_user_access')]
#[ORM\Entity]
class CLpUserAccess
{
    use UserTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    #[ORM\ManyToOne(targetEntity: CLp::class)]
    #[ORM\JoinColumn(name: 'lp_id', referencedColumnName: 'iid', nullable: true, onDelete: 'SET NULL')]
    protected ?CLp $lp = null;

    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'is_open_without_date', type: 'boolean', options: ['default' => 0], nullable: true)]
    protected ?bool $isOpenWithoutDate = false;

    public function __construct()
    {
        $this->isOpenWithoutDate = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLp(): ?CLp
    {
        return $this->lp;
    }

    public function setLp(?CLp $lp): self
    {
        $this->lp = $lp;

        return $this;
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getIsOpenWithoutDate(): ?bool
    {
        return $this->isOpenWithoutDate;
    }

    public function setIsOpenWithoutDate(?bool $isOpenWithoutDate): self
    {
        $this->isOpenWithoutDate = $isOpenWithoutDate;

        return $this;
    }
}
