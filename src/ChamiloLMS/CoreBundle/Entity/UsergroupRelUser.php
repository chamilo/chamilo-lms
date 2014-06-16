<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User;

/**
 * UsergroupRelUser
 *
 * @ORM\Table(name="usergroup_rel_user", indexes={@ORM\Index(name="IDX_739515A9A76ED395", columns={"user_id"}), @ORM\Index(name="IDX_739515A9D2112630", columns={"usergroup_id"})})
 * @ORM\Entity
 */
class UsergroupRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $user;

    /**
     * @var Usergroup
     *
     * @ORM\ManyToOne(targetEntity="Usergroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $usergroup;


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
    public function setUser(User $user = null)
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
     * @return UsergroupRelUser
     */
    public function setUsergroup(Usergroup $usergroup = null)
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
}
