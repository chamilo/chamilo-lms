<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Toolbox;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Toolbox\ToolboxCollectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/toolbox/items',
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_GROUP_STUDENT')",
            name: 'get_toolbox_items',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            provider: ToolboxCollectionProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['toolbox_collection:read']],
)]
final class ToolboxCollection
{
    #[Groups(['toolbox_collection:read'])]
    public bool $enabled = false;

    #[Groups(['toolbox_collection:read'])]
    public bool $canCreate = false;

    #[Groups(['toolbox_collection:read'])]
    public bool $canManage = false;

    /**
     * @var list<array{label: string, value: string}>
     */
    #[Groups(['toolbox_collection:read'])]
    public array $providers = [];

    #[Groups(['toolbox_collection:read'])]
    public int $totalItems = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['toolbox_collection:read'])]
    public array $items = [];
}
