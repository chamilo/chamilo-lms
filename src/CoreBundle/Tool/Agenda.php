<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;

class Agenda extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'agenda';
    }

    public function getIcon(): string
    {
        return 'mdi-calendar-text';
    }

    public function getLink(): string
    {
        return '/resources/ccalendarevent';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'events' => CCalendarEvent::class,
            'event_attachments' => CCalendarEventAttachment::class,
        ];
    }
}
