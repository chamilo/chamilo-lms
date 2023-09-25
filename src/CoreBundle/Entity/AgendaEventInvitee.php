<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'agenda_event_invitee')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'invitee' => AgendaEventInvitee::class,
    'subscriber' => AgendaEventSubscriber::class,
])]
class AgendaEventInvitee
{
    use TimestampableTypedEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: AgendaEventInvitation::class, inversedBy: 'invitees')]
    #[ORM\JoinColumn(name: 'invitation_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?AgendaEventInvitation $invitation;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getInvitation(): ?AgendaEventInvitation
    {
        return $this->invitation;
    }

    public function setInvitation(?AgendaEventInvitation $invitation): self
    {
        $this->invitation = $invitation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
