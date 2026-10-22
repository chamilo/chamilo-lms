<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Conference;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Conference\TeamsMeetingCollectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/conference/teams/meetings',
            security: "is_granted('ROLE_USER')",
            name: 'get_teams_meetings',
            parameters: [
                'scope' => new QueryParameter(
                    schema: ['type' => 'string', 'enum' => ['course', 'personal', 'global']],
                    description: 'Meeting scope: course, personal or global'
                ),
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier'),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            provider: TeamsMeetingCollectionProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['teams_meeting_collection:read']],
)]
final class TeamsMeetingCollection
{
    #[Groups(['teams_meeting_collection:read'])]
    public bool $configured = false;

    #[Groups(['teams_meeting_collection:read'])]
    public string $scope = '';

    #[Groups(['teams_meeting_collection:read'])]
    public bool $canCreate = false;

    #[Groups(['teams_meeting_collection:read'])]
    public bool $personalEnabled = false;

    #[Groups(['teams_meeting_collection:read'])]
    public bool $globalEnabled = false;

    #[Groups(['teams_meeting_collection:read'])]
    public int $totalUpcoming = 0;

    #[Groups(['teams_meeting_collection:read'])]
    public int $totalPast = 0;

    /**
     * @var TeamsMeeting[]
     */
    #[Groups(['teams_meeting_collection:read'])]
    public array $upcoming = [];

    /**
     * @var TeamsMeeting[]
     */
    #[Groups(['teams_meeting_collection:read'])]
    public array $past = [];
}
