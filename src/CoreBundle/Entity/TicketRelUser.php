<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ticket_rel_user')]
#[ORM\Entity]
class TicketRelUser
{
    use UserTrait;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Ticket $ticket;

    #[ORM\Column(name: 'notify', type: 'boolean', nullable: false)]
    protected bool $notify;

    public function __construct(User $user, Ticket $ticket, bool $notify)
    {
        $this->user = $user;
        $this->ticket = $ticket;
        $this->notify = $notify;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function setTicket(Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function isNotify(): bool
    {
        return $this->notify;
    }

    public function setNotify(bool $notify): self
    {
        $this->notify = $notify;

        return $this;
    }
}
