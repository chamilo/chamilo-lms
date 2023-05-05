<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "agenda_reminder")]
class AgendaReminder
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\Column(type: "bigint")]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected $id;

    #[ORM\Column(type: "string", name: "type")]
    protected $type;

    #[ORM\Column(type: "integer", name: "event_id")]
    protected $eventId;

    #[ORM\Column(type: "dateinterval", name: "date_interval")]
    protected $dateInterval;

    #[ORM\Column(type: "boolean", name: "sent")]
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
