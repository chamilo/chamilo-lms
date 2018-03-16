<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelUser.
 *
 * @ORM\Table(name="access_url_rel_user", indexes={@ORM\Index(name="idx_access_url_rel_user_user", columns={"user_id"}), @ORM\Index(name="idx_access_url_rel_user_access_url", columns={"access_url_id"}), @ORM\Index(name="idx_access_url_rel_user_access_url_user", columns={"user_id", "access_url_id"})})
 * @ORM\Entity
 */
class AccessUrlRelUser
{
    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\AccessUrl")
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected $portal;
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param mixed $portal
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
    }
}
