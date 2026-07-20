<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ticket_assigned_log')]
#[ORM\Entity]
class TicketAssignedLog
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(name: 'ticket_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[ORM\Column(name: 'sys_insert_user_id', type: 'integer', nullable: false)]
    protected int $insertUserId;

    #[ORM\Column(name: 'assigned_date', type: 'datetime', nullable: false)]
    protected DateTime $assignedDate;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getInsertUserId(): int
    {
        return $this->insertUserId;
    }

    public function setInsertUserId(int $insertUserId): self
    {
        $this->insertUserId = $insertUserId;

        return $this;
    }

    public function getAssignedDate(): DateTime
    {
        return $this->assignedDate;
    }

    public function setAssignedDate(DateTime $assignedDate): self
    {
        $this->assignedDate = $assignedDate;

        return $this;
    }
}
