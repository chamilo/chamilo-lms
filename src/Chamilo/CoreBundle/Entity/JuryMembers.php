<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Application\Sonata\UserBundle\Entity\User;

/**
 * JuryMembers
 *
 * @ORM\Table(name="jury_members")
 * @ORM\Entity
 */
class JuryMembers
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="role_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $roleId;

     /**
     * @var integer
     *
     * @ORM\Column(name="jury_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $juryId;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="jury")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    //private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Jury", inversedBy="members")
     * @ORM\JoinColumn(name="jury_id", referencedColumnName="id")
     */
    private $jury;

    /**
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="rolesFromJury")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     */
    //private $role;

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function setJury(Jury $jury)
    {
        $this->jury = $jury;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

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
     * Set userId
     *
     * @param integer $userId
     * @return JuryMembers
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set roleId
     *
     * @param integer $roleId
     * @return JuryMembers
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;

        return $this;
    }

    /**
     * Get roleId
     *
     * @return integer
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

     /**
     * Set juryId
     *
     * @param integer $id
     * @return JuryMembers
     */
    public function setJuryId($id)
    {
        $this->juryId = $id;

        return $this;
    }

    /**
     * Get juryId
     *
     * @return integer
     */
    public function getJuryId()
    {
        return $this->juryId;
    }
}
