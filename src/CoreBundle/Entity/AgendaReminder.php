<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateInterval;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'agenda_reminder')]
class AgendaReminder
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['calendar_event:read'])]
    protected ?int $id = null;

    #[ORM\Column(name: 'date_interval', type: 'dateinterval')]
    protected DateInterval $dateInterval;

    #[ORM\Column(name: 'sent', type: 'boolean')]
    protected bool $sent;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    public int $count;

    #[Groups(['calendar_event:write', 'calendar_event:read'])]
    public string $period;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'reminders')]
    #[ORM\JoinColumn(referencedColumnName: 'iid', nullable: false)]
    private ?CCalendarEvent $event = null;

    public function __construct()
    {
        $this->sent = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateInterval(): DateInterval
    {
        return $this->dateInterval;
    }

    public function setDateInterval(DateInterval $dateInterval): self
    {
        $this->dateInterval = $dateInterval;

        return $this;
    }

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function setSent(bool $sent): self
    {
        $this->sent = $sent;

        return $this;
    }

    public function getEvent(): ?CCalendarEvent
    {
        return $this->event;
    }

    public function setEvent(?CCalendarEvent $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function decodeDateInterval(): static
    {
        $count = max(0, (int) $this->count);
        $period = (string) $this->period;

        $isoSpec = match ($period) {
            'i' => "PT{$count}M",
            'h' => "PT{$count}H",
            'd' => "P{$count}D",
            'w' => "P{$count}W",
            default => "PT0M",
        };

        $this->dateInterval = new \DateInterval($isoSpec);

        return $this;
    }

    public function encodeDateInterval(): static
    {
        if ($this->dateInterval->i) {
            $this->count = $this->dateInterval->i;
            $this->period = 'i';
        } elseif ($this->dateInterval->h) {
            $this->count = $this->dateInterval->h;
            $this->period = 'h';
        } elseif ($this->dateInterval->d) {
            $this->count = $this->dateInterval->d;
            $this->period = 'd';
        } else {
            $this->count = (int) $this->dateInterval->format('%a');
            $this->period = 'd';
        }

        return $this;
    }
}
