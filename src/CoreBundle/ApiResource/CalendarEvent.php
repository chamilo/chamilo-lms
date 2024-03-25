<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

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
        #[Groups(['calendar_event:read'])]
        public ?ResourceNode $resourceNode = null,
        ?array $resourceLinkListFromEntity = null,
        #[Groups(['calendar_event:read'])]
        public ?string $type = null,
    ) {
        $this->resourceLinkListFromEntity = $resourceLinkListFromEntity;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }
}
