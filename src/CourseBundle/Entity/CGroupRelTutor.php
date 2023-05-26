<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_group_rel_tutor')]
#[ORM\Index(name: 'course', columns: ['c_id'])]
#[ORM\Entity]
class CGroupRelTutor
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\Column(name: 'c_id', type: 'integer')]
    protected int $cId;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'courseGroupsAsTutor')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CourseBundle\Entity\CGroup::class, inversedBy: 'tutors')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CGroup $group;

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setGroup(CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return CGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set cId.
     *
     * @return CGroupRelTutor
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
