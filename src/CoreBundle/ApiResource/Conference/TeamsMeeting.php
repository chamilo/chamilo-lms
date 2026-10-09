<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Conference;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Conference\TeamsMeetingCancelProcessor;
use Chamilo\CoreBundle\State\Conference\TeamsMeetingCreateProcessor;
use Chamilo\CoreBundle\State\Conference\TeamsMeetingProvider;
use Chamilo\CoreBundle\State\Conference\TeamsMeetingUpdateProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/conference/teams/meetings/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            name: 'get_teams_meeting',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier'),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            provider: TeamsMeetingProvider::class,
        ),
        new Post(
            uriTemplate: '/conference/teams/meetings',
            security: "is_granted('ROLE_USER')",
            read: false,
            input: TeamsMeetingCreateInput::class,
            denormalizationContext: ['groups' => ['teams_meeting:create']],
            name: 'create_teams_meeting',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier'),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: TeamsMeetingCreateProcessor::class,
        ),
        new Post(
            uriTemplate: '/conference/teams/meetings/{id}/update',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            read: false,
            input: TeamsMeetingUpdateInput::class,
            denormalizationContext: ['groups' => ['teams_meeting:update']],
            name: 'update_teams_meeting',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier'),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: TeamsMeetingUpdateProcessor::class,
        ),
        new Post(
            uriTemplate: '/conference/teams/meetings/{id}/cancel',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_USER')",
            read: false,
            input: false,
            name: 'cancel_teams_meeting',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier'),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: TeamsMeetingCancelProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['teams_meeting:read']],
)]
final class TeamsMeeting
{
    #[ApiProperty(identifier: true)]
    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?int $id = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public string $scope = '';

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public string $title = '';

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?string $startAt = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?string $endAt = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?string $joinUrl = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public string $status = 'upcoming';

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public bool $canJoin = false;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public bool $canManage = false;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?int $courseId = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?int $sessionId = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?int $groupId = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?string $organizerName = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?string $createdAt = null;

    #[Groups(['teams_meeting:read', 'teams_meeting_collection:read'])]
    public ?int $calendarId = null;
}
