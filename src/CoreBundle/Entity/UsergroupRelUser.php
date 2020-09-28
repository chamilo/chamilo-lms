<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class UsergroupRelUser.
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
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="classes", cascade={"persist"})
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
     * @var int
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false)
     */
    protected $relationType;

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
     * Set usergroup.
     *
     * @return UsergroupRelUser
     */
    public function setUsergroup(Usergroup $usergroup)
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    /**
     * Get usergroup.
     *
     * @return Usergroup
     */
    public function getUsergroup()
    {
        return $this->usergroup;
    }

    /**
     * Set relationType.
     *
     * @param int $relationType
     *
     * @return $this
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
    }
}
