<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="agenda_event_invitee")
 * Add @ to the next lineactivating the agenda_collective_invitations configuration setting.
 * ORM\Entity()
 * ORM\InheritanceType("SINGLE_TABLE")
 * ORM\DiscriminatorColumn(name="type", type="string")
 * ORM\DiscriminatorMap({
 *     "invitee" = "Chamilo\CoreBundle\Entity\AgendaEventInvitee",
 *     "subscriber" = "Chamilo\CoreBundle\Entity\AgendaEventSubscriber"
 * })
 */
class AgendaEventInvitee
{
    use TimestampableTypedEntity;

    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var AgendaEventInvitation|null
     *
     * @ORM\ManyToOne(targetEntity="AgendaEventInvitation", inversedBy="invitees")
     * @ORM\JoinColumn(name="invitation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $invitation;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getInvitation(): ?AgendaEventInvitation
    {
        return $this->invitation;
    }

    public function setInvitation(?AgendaEventInvitation $invitation): AgendaEventInvitee
    {
        $this->invitation = $invitation;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): AgendaEventInvitee
    {
        $this->user = $user;

        return $this;
    }
}
