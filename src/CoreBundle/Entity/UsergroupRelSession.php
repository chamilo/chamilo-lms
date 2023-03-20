<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="usergroup_rel_session")
 * @ORM\Entity
 */
class UsergroupRelSession
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup", inversedBy="sessions")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Usergroup $usergroup;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Session $session;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUsergroup(): Usergroup
    {
        return $this->usergroup;
    }

    public function setUsergroup(Usergroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function setSession(Session $session): self
    {
        $this->session = $session;

        return $this;
    }
}
