<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelUser.
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
    public $relationTypeList = [
        0 => 'student',
        1 => 'drh',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    protected $session;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="relation_type", type="integer", nullable=false, unique=false)
     */
    protected $relationType;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="integer", nullable=true)
     */
    protected $duration;

    /**
     * @var int
     *
     * @ORM\Column(name="moved_to", type="integer", nullable=true, unique=false)
     */
    protected $movedTo;

    /**
     * @var int
     *
     * @ORM\Column(name="moved_status", type="integer", nullable=true, unique=false)
     */
    protected $movedStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="moved_at", type="datetime", nullable=true, unique=false)
     */
    protected $movedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="registered_at", type="datetime", nullable=false, unique=false)
     */
    protected $registeredAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->movedTo = null;
        $this->movedStatus = null;
        $this->movedAt = null;
        $this->registeredAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Set Session.
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
     * Get Session.
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set User.
     *
     * @param User $user
     *
     * @return SessionRelUser
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get idUser.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set relationType.
     *
     * @param int $relationType
     *
     * @return SessionRelUser
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Set relationTypeByName.
     *
     * @param string $relationType
     *
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
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * Set movedTo.
     *
     * @param int $movedTo
     *
     * @return SessionRelUser
     */
    public function setMovedTo($movedTo)
    {
        $this->movedTo = $movedTo;

        return $this;
    }

    /**
     * Get movedTo.
     *
     * @return int
     */
    public function getMovedTo()
    {
        return $this->movedTo;
    }

    /**
     * Set movedStatus.
     *
     * @param int $movedStatus
     *
     * @return SessionRelUser
     */
    public function setMovedStatus($movedStatus)
    {
        $this->movedStatus = $movedStatus;

        return $this;
    }

    /**
     * Get movedStatus.
     *
     * @return int
     */
    public function getMovedStatus()
    {
        return $this->movedStatus;
    }

    /**
     * Set movedAt.
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
     * Get movedAt.
     *
     * @return \DateTime
     */
    public function getMovedAt()
    {
        return $this->movedAt;
    }

    /**
     * Set registeredAt.
     *
     * @return $this
     */
    public function setRegisteredAt(\DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * Get registeredAt.
     *
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
     *
     * @return SessionRelUser
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }
}
