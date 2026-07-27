<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Announcement;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Announcement\AnnouncementActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AnnouncementAction',
    operations: [
        new Post(
            uriTemplate: '/announcement/{id}/visibility',
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Change announcement visibility',
                parameters: [
                    new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_announcement_visibility',
            processor: AnnouncementActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/announcement/{id}/move',
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Move an announcement',
                parameters: [
                    new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_announcement_move',
            processor: AnnouncementActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/announcement/{id}/delete',
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Delete an announcement',
                parameters: [
                    new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_announcement_delete',
            processor: AnnouncementActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/announcement/delete-selected',
            openapi: new Operation(
                summary: 'Delete selected announcements',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_announcement_delete_selected',
            processor: AnnouncementActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/announcement/delete-all',
            openapi: new Operation(
                summary: 'Delete all announcements in the current context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_announcement_delete_all',
            processor: AnnouncementActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['announcement_action:read']],
    denormalizationContext: ['groups' => ['announcement_action:write']],
)]
final class AnnouncementAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['announcement_action:read'])]
    public ?int $id = null;

    /**
     * @var array<int, int>
     */
    #[Groups(['announcement_action:write'])]
    public array $ids = [];

    #[Groups(['announcement_action:write'])]
    public string $direction = '';

    #[Groups(['announcement_action:write'])]
    public ?int $visibility = null;

    #[Groups(['announcement_action:write'])]
    public string $csrfToken = '';

    #[Groups(['announcement_action:read'])]
    public bool $success = false;

    /**
     * @var array<int, int>
     */
    #[Groups(['announcement_action:read'])]
    public array $affectedIds = [];
}
