<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelUser
 *
 * @ORM\Table(name="session_rel_user", indexes={@ORM\Index(name="idx_session_rel_user_id_user_moved", columns={"id_user", "moved_to"})})
 * @ORM\Entity
 */
class SessionRelUser
{
    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="id_session", referencedColumnName="id")
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     */
    private $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $relationType;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_to", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $movedTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_status", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $movedStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moved_at", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $movedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moved_to = null;
        $this->movedStatus = null;
        $this->movedAt = null;
    }


    /**
     * Set idSession
     *
     * @param Session $session
     *
     * @return SessionRelUser
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Get Session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set idUser
     *
     * @param integer $user
     * @return SessionRelUser
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return integer
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set relationType
     *
     * @param integer $relationType
     * @return SessionRelUser
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

    /**
     * Set movedTo
     *
     * @param integer $movedTo
     * @return SessionRelUser
     */
    public function setMovedTo($movedTo)
    {
        $this->movedTo = $movedTo;

        return $this;
    }

    /**
     * Get movedTo
     *
     * @return integer
     */
    public function getMovedTo()
    {
        return $this->movedTo;
    }

    /**
     * Set movedStatus
     *
     * @param integer $movedStatus
     * @return SessionRelUser
     */
    public function setMovedStatus($movedStatus)
    {
        $this->movedStatus = $movedStatus;

        return $this;
    }

    /**
     * Get movedStatus
     *
     * @return integer
     */
    public function getMovedStatus()
    {
        return $this->movedStatus;
    }

    /**
     * Set movedAt
     *
     * @param \DateTime $movedAt
     * @return SessionRelUser
     */
    public function setMovedAt($movedAt)
    {
        $this->movedAt = $movedAt;

        return $this;
    }

    /**
     * Get movedAt
     *
     * @return \DateTime
     */
    public function getMovedAt()
    {
        return $this->movedAt;
    }
}
