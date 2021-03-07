<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * SessionRelUser.
 *
 * @ORM\Table(
 *     name="session_rel_user",
 *     indexes={
 *         @ORM\Index(name="idx_session_rel_user_id_user_moved", columns={"user_id", "moved_to"})
 *     }
 * )
 * @ORM\Entity
 */
class SessionRelUser
{
    use UserTrait;

    /**
     * @var string[]
     */
    public array $relationTypeList = [
        0 => 'student',
        1 => 'drh',
    ];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    protected Session $session;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="sessions", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected User $user;

    /**
     * @ORM\Column(name="relation_type", type="integer")
     */
    protected int $relationType;

    /**
     * @ORM\Column(name="duration", type="integer", nullable=false)
     */
    protected int $duration;

    /**
     * @ORM\Column(name="moved_to", type="integer", nullable=true, unique=false)
     */
    protected ?int $movedTo;

    /**
     * @ORM\Column(name="moved_status", type="integer", nullable=true, unique=false)
     */
    protected ?int $movedStatus;

    /**
     * @ORM\Column(name="moved_at", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $movedAt = null;

    /**
     * @ORM\Column(name="registered_at", type="datetime")
     */
    protected DateTime $registeredAt;

    public function __construct()
    {
        $this->duration = 0;
        $this->movedTo = null;
        $this->movedStatus = null;
        $this->registeredAt = new DateTime('now', new DateTimeZone('UTC'));
    }

    public function setSession(Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Set relationTypeByName.
     *
     * @return SessionRelUser
     */
    public function setRelationTypeByName(string $relationType): self
    {
        if (isset($this->relationTypeList[$relationType])) {
            $this->setRelationType((int) $this->relationTypeList[$relationType]);
        }

        return $this;
    }

    public function getRelationType(): int
    {
        return $this->relationType;
    }

    /**
     * Set movedTo.
     *
     * @return SessionRelUser
     */
    public function setMovedTo(int $movedTo)
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
     * @return SessionRelUser
     */
    public function setMovedStatus(int $movedStatus)
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
     * @return SessionRelUser
     */
    public function setMovedAt(DateTime $movedAt)
    {
        $this->movedAt = $movedAt;

        return $this;
    }

    /**
     * Get movedAt.
     *
     * @return DateTime
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
    public function setRegisteredAt(DateTime $registeredAt)
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * Get registeredAt.
     *
     * @return DateTime
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
     * @return SessionRelUser
     */
    public function setDuration(int $duration)
    {
        $this->duration = $duration;

        return $this;
    }
}
