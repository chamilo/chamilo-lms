<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CalendarEventRecipientsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * The users and groups a course event can be sent to, see AgendaHelper::canAddLinks().
 */
#[ApiResource(
    shortName: 'CalendarEventRecipients',
    operations: [
        new Get(
            uriTemplate: '/calendar/course-recipients',
            openapi: new Operation(summary: 'Users and groups of the current course a calendar event can be sent to'),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_calendar_event_recipients',
            provider: CalendarEventRecipientsProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['calendar_event_recipients:read']],
)]
final class CalendarEventRecipients
{
    #[ApiProperty(identifier: true)]
    #[Groups(['calendar_event_recipients:read'])]
    public string $id = 'calendar_event_recipients';

    /**
     * @var list<array{id: int, label: string}>
     */
    #[Groups(['calendar_event_recipients:read'])]
    public array $users = [];

    /**
     * @var list<array{id: int, label: string}>
     */
    #[Groups(['calendar_event_recipients:read'])]
    public array $groups = [];
}
