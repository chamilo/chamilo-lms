<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Symfony\Component\Security\Core\Role\Role as SymfonyRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="roles")
 * @ORM\Entity()
 */
class Role extends SymfonyRole implements \Serializable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(name="role", type="string", length=255, unique=true)
     */
    private $role;

    /**
     * @ORM\ManyToMany(targetEntity="Application\Sonata\UserBundle\Entity\User", mappedBy="roles")
     * @ORM\JoinTable(name="users_roles",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")})
     */
    private $users;

     /**
     * @ORM\OneToMany(targetEntity="JuryMembers", mappedBy="role")
     **/
    private $rolesFromJury;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * Return the role field.
     * @return string
     */
    public function __toString()
    {
        return (string) $this->role;
    }

    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
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
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

     /**
     * Set name
     *
     * @param string $role
     * @return Role
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

     /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        /*
         * ! Don't serialize $users field !
         */
        return \serialize(array(
            $this->id,
            $this->name,
            $this->role
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->name,
            $this->role
        ) = \unserialize($serialized);
    }
}
