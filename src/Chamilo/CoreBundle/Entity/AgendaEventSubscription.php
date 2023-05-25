<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ORM\Entity()
 */
class AgendaEventSubscription extends AgendaEventInvitation
{
    public const SUBSCRIPTION_NO = 0;
    public const SUBSCRIPTION_ALL = 1;
    public const SUBSCRIPTION_CLASS = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="max_attendees", type="integer", nullable=false, options={"default": 0})
     */
    protected $maxAttendees = 0;

    public function getMaxAttendees(): int
    {
        return $this->maxAttendees;
    }

    public function setMaxAttendees(int $maxAttendees): self
    {
        $this->maxAttendees = $maxAttendees;

        return $this;
    }
}
