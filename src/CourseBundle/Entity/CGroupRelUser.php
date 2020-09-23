<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupRelUser.
 *
 * @ORM\Table(
 *  name="c_group_rel_user",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CGroupRelUser
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="courseGroupsAsMember")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var CGroup
     *
     * @ORM\ManyToOne(targetEntity="CGroup", inversedBy="members")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=false)
     */
    protected $group;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=50, nullable=false)
     */
    protected $role;

    /**
     * Set group.
     *
     * @return CGroupRelUser
     */
    public function setGroup(CGroup $group)
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
     * Set status.
     *
     * @param int $status
     *
     * @return CGroupRelUser
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set role.
     *
     * @param string $role
     *
     * @return CGroupRelUser
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CGroupRelUser
     */
    public function setCId($cId)
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
