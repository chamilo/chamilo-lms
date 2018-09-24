<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CGroupRelTutor.
 *
 * @ORM\Table(
 *  name="c_group_rel_tutor",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CGroupRelTutor
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
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="courseGroupsAsTutor")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var CGroupInfo
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroupInfo", inversedBy="tutors")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=false)
     */
    protected $group;

    /**
     * Set userId.
     *
     * @param User $user
     *
     * @return CGroupRelTutor
     */
    public function setUser(User $user)
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
     * @return CGroupRelTutor
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
     * Set id.
     *
     * @param int $id
     *
     * @return CGroupRelTutor
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
     * @return CGroupRelTutor
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
