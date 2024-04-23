<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'notification_event_rel_user')]
class NotificationEventRelUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NotificationEvent::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false)]
    private NotificationEvent $event;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): NotificationEvent
    {
        return $this->event;
    }

    public function setEvent(NotificationEvent $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
