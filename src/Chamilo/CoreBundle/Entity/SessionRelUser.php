<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelUser
 *
 * @ORM\Table(
 *    name="session_rel_user",
 *      indexes={
 *          @ORM\Index(name="idx_session_rel_user_id_user_moved", columns={"user_id", "moved_to"})
 *      }
 * )
 * @ORM\Entity
 */
class SessionRelUser
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false, unique=false)
     */
    private $relationType;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    private $duration;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_to", type="integer", nullable=true, unique=false)
     */
    private $movedTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="moved_status", type="integer", nullable=true, unique=false)
     */
    private $movedStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moved_at", type="datetime", nullable=true, unique=false)
     */
    private $movedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="registered_at", type="datetime", nullable=false, unique=false)
     */
    private $registeredAt;

    public $relationTypeList = array(
        0 => 'student',
        1 => 'drh'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moved_to = null;
        $this->movedStatus = null;
        $this->movedAt = null;
        $this->registeredAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Set Session
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
     * Set User
     *
     * @param User $user
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
     * @return User
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
     * Set relationTypeByName
     *
     * @param string $relationType
     * @return SessionRelUser
     */
    public function setRelationTypeByName($relationType)
    {
        if (isset($this->relationTypeList[$relationType])) {
            $this->setRelationType($this->relationTypeList[$relationType]);
        }

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
     *
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

    /**
     * Set registeredAt
     * @param \DateTime $registeredAt
     *
     * @return $this
     */
    public function setRegisteredAt(\DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * Get registeredAt
     * @return \DateTime
     */
    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     * @return SessionRelUser
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

}
