<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class UsergroupRelUser
 *
 * @ORM\Table(
 *     name="usergroup_rel_user",
 *     indexes={
 *          @ORM\Index(name="IDX_739515A9A76ED395", columns={"user_id"}),
 *          @ORM\Index(name="IDX_739515A9D2112630", columns={"usergroup_id"})
 *     }
 * )
 * @ORM\Entity
 */
class UsergroupRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    private $relationType;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="classes", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var Usergroup
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id")
     */
    protected $usergroup;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return UsergroupRelUser
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set usergroup
     *
     * @param Usergroup $usergroup
     *
     * @return UsergroupRelUser
     */
    public function setUsergroup(Usergroup $usergroup)
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    /**
     * Get usergroup
     *
     * @return Usergroup
     */
    public function getUsergroup()
    {
        return $this->usergroup;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return GroupRelUser
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType
     *
     * @return integer
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

}
