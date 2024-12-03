<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Usergroup;
use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupRelUsergroup.
 */
#[ORM\Table(name: 'c_group_rel_usergroup')]
#[ORM\Entity]
class CGroupRelUsergroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CGroup $group;

    #[ORM\ManyToOne(targetEntity: Usergroup::class)]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Usergroup $usergroup;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Course $course = null;

    #[ORM\Column(name: 'ready_autogroup', type: 'boolean', nullable: false)]
    protected bool $readyAutogroup;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGroup(): CGroup
    {
        return $this->group;
    }

    public function setGroup(CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    public function getUsergroup(): Usergroup
    {
        return $this->usergroup;
    }

    public function setUsergroup(Usergroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function isReadyAutogroup(): bool
    {
        return $this->readyAutogroup;
    }

    public function setReadyAutogroup(bool $readyAutogroup): self
    {
        $this->readyAutogroup = $readyAutogroup;

        return $this;
    }
}
