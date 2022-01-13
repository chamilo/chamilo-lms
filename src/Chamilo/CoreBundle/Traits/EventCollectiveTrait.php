<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\AgendaEventInvitation;
use Doctrine\ORM\Mapping as ORM;

trait EventCollectiveTrait
{
    /**
     * @var AgendaEventInvitation|null
     *
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\AgendaEventInvitation",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="agenda_event_invitation_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $invitation;

    /**
     * @ORM\Column(name="collective", type="boolean", nullable=false)
     */
    protected $collective = false;

    public function hasInvitation(): bool
    {
        return $this->invitation instanceof AgendaEventInvitation;
    }

    public function getInvitation(): ?AgendaEventInvitation
    {
        return $this->invitation;
    }

    /**
     * @return $this
     */
    public function setInvitation(AgendaEventInvitation $invitation)
    {
        $this->invitation = $invitation;

        return $this;
    }

    public function isCollective(): bool
    {
        return $this->collective;
    }

    /**
     * @return $this
     */
    public function setCollective(bool $collective)
    {
        $this->collective = $collective;

        return $this;
    }
}
