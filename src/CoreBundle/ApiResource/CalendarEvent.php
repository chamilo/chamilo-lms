<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

class CalendarEvent extends AbstractResource
{
    public function __construct(
        #[Groups(['calendar_event:read'])]
        public ?string $id = null,
        #[Groups(['calendar_event:read'])]
        public ?string $title = null,
        #[Groups(['calendar_event:read'])]
        public ?string $content = null,
        #[Groups(['calendar_event:read'])]
        public ?DateTime $startDate = null,
        #[Groups(['calendar_event:read'])]
        public ?DateTime $endDate = null,
        #[Groups(['calendar_event:read'])]
        public bool $allDay = false,
        #[Groups(['calendar_event:read'])]
        public ?string $url = null,
        #[Groups(['calendar_event:read'])]
        public ?string $invitationType = null,
        #[Groups(['calendar_event:read'])]
        public bool $collective = false,
        #[Groups(['calendar_event:read'])]
        public int $subscriptionVisibility = CCalendarEvent::SUBSCRIPTION_VISIBILITY_NO,
        #[Groups(['calendar_event:read'])]
        public ?int $subscriptionItemId = null,
        #[Groups(['calendar_event:read'])]
        public ?string $subscriptionItemTitle = null,
        #[Groups(['calendar_event:read'])]
        public int $maxAttendees = 0,
        /**
         * @var Collection<int, AgendaReminder>|null
         */
        #[Groups(['calendar_event:read'])]
        public ?Collection $reminders = null,
        #[Groups(['calendar_event:read'])]
        #[MaxDepth(1)]
        public ?ResourceNode $resourceNode = null,
        public ?array $resourceLinkListFromEntity = null,
        #[Groups(['calendar_event:read'])]
        public ?string $color = null,
        #[Groups(['calendar_event:read'])]
        public ?string $type = null,
    ) {}

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    #[Groups(['calendar_event:read'])]
    public function getObjectType(): string
    {
        return preg_replace('/_\d+$/', '', $this->id);
    }
}
