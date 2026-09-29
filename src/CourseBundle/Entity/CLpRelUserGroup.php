<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Traits\CourseTrait;
use Chamilo\CoreBundle\Traits\SessionTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'c_lp_rel_usergroup')]
#[ORM\Entity]
class CLpRelUserGroup
{
    use CourseTrait;
    use SessionTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CLp::class)]
    #[ORM\JoinColumn(name: 'lp_id', referencedColumnName: 'iid')]
    protected CLp $lp;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false)]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true)]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', nullable: true)]
    protected ?Usergroup $userGroup = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserGroup(): ?Usergroup
    {
        return $this->userGroup;
    }

    public function setUserGroup(?Usergroup $userGroup): self
    {
        $this->userGroup = $userGroup;

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
}
