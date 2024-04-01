<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\Controller\Api\DeleteRemindersByEventAction;
use Chamilo\CoreBundle\State\AgendaReminderProcessor;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use DateInterval;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'agenda_reminder')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(processor: AgendaReminderProcessor::class),
        new Post(
            uriTemplate: '/agenda_reminders/delete_by_event',
            controller: DeleteRemindersByEventAction::class,
            openapiContext: ['summary' => 'Deletes all reminders for a specific event'],
            denormalizationContext: ['groups' => ['agenda_reminder:delete_by_event']],
            security: "is_granted('ROLE_USER')"
        ),
    ],
    normalizationContext: ['groups' => ['agenda_reminder:read']],
    denormalizationContext: ['groups' => ['agenda_reminder:write']],
    security: "is_granted('ROLE_USER')"
)]
#[ApiFilter(SearchFilter::class, properties: ['eventId' => 'exact'])]
class AgendaReminder
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'type', type: 'string')]
    #[Groups(['agenda_reminder:write', 'agenda_reminder:read'])]
    protected string $type;

    #[ORM\Column(name: 'event_id', type: 'integer')]
    #[Groups(['agenda_reminder:write', 'agenda_reminder:read'])]
    protected int $eventId;

    #[ORM\Column(name: 'date_interval', type: 'dateinterval')]
    #[Groups(['agenda_reminder:write', 'agenda_reminder:read'])]
    protected DateInterval $dateInterval;

    #[ORM\Column(name: 'sent', type: 'boolean')]
    #[Groups(['agenda_reminder:write', 'agenda_reminder:read'])]
    protected bool $sent;

    public function __construct()
    {
        $this->sent = false;
        $this->type = 'personal';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function setEventId(int $eventId): self
    {
        $this->eventId = $eventId;

        return $this;
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
}
