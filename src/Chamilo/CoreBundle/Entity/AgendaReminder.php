<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="agenda_reminder")
 * Add @ to the next line when activatiing the agenda_reminders configuration setting
 * ORM\Entity()
 */
class AgendaReminder
{
    use TimestampableTypedEntity;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="event_id", type="integer")
     */
    protected $eventId;

    /**
     * @var \DateInterval
     *
     * @ORM\Column(name="date_interval", type="dateinterval")
     */
    protected $dateInterval;

    /**
     * @var bool
     *
     * @ORM\Column(name="sent", type="boolean")
     */
    protected $sent;

    public function __construct()
    {
        $this->sent = false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): AgendaReminder
    {
        $this->type = $type;

        return $this;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function setEventId(int $eventId): AgendaReminder
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function getDateInterval(): \DateInterval
    {
        return $this->dateInterval;
    }

    public function setDateInterval(\DateInterval $dateInterval): AgendaReminder
    {
        $this->dateInterval = $dateInterval;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): AgendaReminder
    {
        $this->sent = $sent;

        return $this;
    }
}
