<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use Chamilo\CoreBundle\Entity\ResourceNode;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

class CalendarEvent
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
        public ?ResourceNode $resourceNode = null,
    ) {}
}
