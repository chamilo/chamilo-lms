<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ScheduledAnnouncement.
 *
 * @ORM\Table(name="scheduled_announcements")
 * @ORM\Entity
 */
class ScheduledAnnouncement
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=false, unique=false)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", unique=false)
     */
    protected $message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime", nullable=true)
     */
    protected $date;

    /**
     * @var bool
     *
     * @ORM\Column(name="sent", type="boolean")
     */
    protected $sent;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    protected $cId;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return ScheduledAnnouncement
     */
    public function setId(int $id): ScheduledAnnouncement
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return ScheduledAnnouncement
     */
    public function setSubject(string $subject): ScheduledAnnouncement
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return ScheduledAnnouncement
     */
    public function setMessage(string $message): ScheduledAnnouncement
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     *
     * @return ScheduledAnnouncement
     */
    public function setDate(\DateTime $date): ScheduledAnnouncement
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * @param bool $sent
     *
     * @return ScheduledAnnouncement
     */
    public function setSent(bool $sent): ScheduledAnnouncement
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionId(): int
    {
        return $this->sessionId;
    }

    /**
     * @param int $sessionId
     *
     * @return ScheduledAnnouncement
     */
    public function setSessionId(int $sessionId): ScheduledAnnouncement
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCId(): int
    {
        return $this->cId;
    }

    /**
     * @param int $cId
     *
     * @return ScheduledAnnouncement
     */
    public function setCId(int $cId): ScheduledAnnouncement
    {
        $this->cId = $cId;

        return $this;
    }
}
