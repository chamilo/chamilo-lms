<?php

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\SessionTrait;
use Chamilo\CoreBundle\Traits\UserTrait;
use Chamilo\CourseBundle\Repository\CLpRelUserRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'c_lp_rel_user')]
#[ORM\Entity(repositoryClass: CLpRelUserRepository::class)]
class CLpRelUser
{
    use CourseTrait;
    use SessionTrait;
    use UserTrait;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\ManyToOne(targetEntity: CLp::class)]
    #[ORM\JoinColumn(name: 'lp_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CLp $lp;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false)]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true)]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?User $creatorUser;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CGroup $group = null;

    #[ORM\Column(name: 'start_date', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $startDate = null;

    #[ORM\Column(name: 'end_date', type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $endDate = null;

    #[ORM\Column(name: 'is_open_without_date', type: 'boolean', nullable: false, options: ['default' => 0])]
    protected bool $isOpenWithoutDate = false;

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getIsOpenWithoutDate(): bool
    {
        return $this->isOpenWithoutDate;
    }

    public function setIsOpenWithoutDate(bool $isOpenWithoutDate): self
    {
        $this->isOpenWithoutDate = $isOpenWithoutDate;

        return $this;
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setIid(int $iid): self
    {
        $this->iid = $iid;

        return $this;
    }

    public function getLp(): CLp
    {
        return $this->lp;
    }

    public function setLp(CLp $lp): self
    {
        $this->lp = $lp;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatorUser(): ?User
    {
        return $this->creatorUser;
    }

    public function setCreatorUser(?User $creatorUser): self
    {
        $this->creatorUser = $creatorUser;

        return $this;
    }
}
