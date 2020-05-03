<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelUser.
 *
 * @ORM\Table(
 *     name="access_url_rel_user",
 *     indexes={
 *      @ORM\Index(name="idx_access_url_rel_user_user", columns={"user_id"}),
 *      @ORM\Index(name="idx_access_url_rel_user_access_url", columns={"access_url_id"}),
 *      @ORM\Index(name="idx_access_url_rel_user_access_url_user", columns={"user_id", "access_url_id"})
 *     }
 * )
 * @ORM\Entity
 */
class AccessUrlRelUser
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="portals")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="AccessUrl", inversedBy="user", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected $url;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }
}
