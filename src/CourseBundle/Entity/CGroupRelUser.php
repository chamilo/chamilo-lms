<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\UserBundle\Entity\User;
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
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="courseGroupsAsMember")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var CGroupInfo
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo", inversedBy="members")
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
     * Set userId.
     *
     * @param int $user
     *
     * @return CGroupRelUser
     */
    public function setUser($user)
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

    /**
     * Set group.
     *
     * @param CGroupInfo $group
     *
     * @return CGroupRelUser
     */
    public function setGroup(CGroupInfo $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return CGroupInfo
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
     * Set id.
     *
     * @param int $id
     *
     * @return CGroupRelUser
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
