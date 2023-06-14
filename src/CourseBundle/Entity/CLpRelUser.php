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

#[ORM\Table(name: "c_lp_rel_user")]
#[ORM\Entity(repositoryClass: CLpRelUserRepository::class)]
class CLpRelUser
{
    use CourseTrait;
    use SessionTrait;
    use UserTrait;

    #[ORM\Column(name: "iid", type: "integer")]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\ManyToOne(targetEntity: CLp::class)]
    #[ORM\JoinColumn(name: "lp_id", referencedColumnName: "iid")]
    protected CLp $lp;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: "c_id", referencedColumnName: "id", nullable: false)]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: "session_id", referencedColumnName: "id", nullable: true)]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    protected User $user;

    #[Gedmo\Timestampable(on: "create")]
    #[ORM\Column(name: "created_at", type: "datetime", nullable: false)]
    protected DateTime $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ["persist"])]
    #[ORM\JoinColumn(name: "creator_id", referencedColumnName: "id")]
    protected $creatorUser;

    public function getIid(): int
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

    public function getCreatorUser()
    {
        return $this->creatorUser;
    }

    public function setCreatorUser(User $creatorUser): self
    {
        $this->creatorUser = $creatorUser;

        return $this;
    }
}
