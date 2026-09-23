<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Toolbox;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\Toolbox\ToolboxGenerateProcessor;
use Chamilo\CoreBundle\State\Toolbox\ToolboxItemProcessor;
use Chamilo\CoreBundle\State\Toolbox\ToolboxItemProvider;
use Chamilo\CoreBundle\State\Toolbox\ToolboxRestoreVersionProcessor;
use Chamilo\CoreBundle\State\Toolbox\ToolboxVisibilityProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/toolbox/items/{id}',
            requirements: ['id' => '\d+'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_GROUP_STUDENT')",
            name: 'get_toolbox_item',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            provider: ToolboxItemProvider::class,
        ),
        new Post(
            uriTemplate: '/toolbox/items',
            security: self::TEACHER_SECURITY,
            read: false,
            name: 'create_toolbox_item',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: ToolboxItemProcessor::class,
        ),
        new Post(
            uriTemplate: '/toolbox/items/{id}/generate',
            requirements: ['id' => '\d+'],
            security: self::TEACHER_SECURITY,
            read: false,
            status: Response::HTTP_OK,
            input: ToolboxGenerateInput::class,
            output: false,
            denormalizationContext: ['groups' => ['toolbox_generate:write']],
            name: 'generate_toolbox_item_version',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: ToolboxGenerateProcessor::class,
        ),
        new Post(
            uriTemplate: '/toolbox/items/{id}/visibility',
            requirements: ['id' => '\d+'],
            security: self::TEACHER_SECURITY,
            read: false,
            status: Response::HTTP_NO_CONTENT,
            input: ToolboxVisibilityInput::class,
            output: false,
            denormalizationContext: ['groups' => ['toolbox_visibility:write']],
            name: 'set_toolbox_item_visibility',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: ToolboxVisibilityProcessor::class,
        ),
        new Post(
            uriTemplate: '/toolbox/items/{id}/versions/{versionNumber}/restore',
            requirements: ['id' => '\d+', 'versionNumber' => '\d+'],
            security: self::TEACHER_SECURITY,
            read: false,
            status: Response::HTTP_NO_CONTENT,
            input: false,
            output: false,
            name: 'restore_toolbox_item_version',
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Course identifier', required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Session identifier'),
                'gid' => new QueryParameter(schema: ['type' => 'integer'], description: 'Group identifier'),
            ],
            processor: ToolboxRestoreVersionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['toolbox_item:read']],
    denormalizationContext: ['groups' => ['toolbox_item:write']],
)]
final class ToolboxItem
{
    private const string TEACHER_SECURITY = "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')";

    #[ApiProperty(identifier: true)]
    #[Groups(['toolbox_item:read'])]
    public ?int $id = null;

    #[Groups(['toolbox_item:read', 'toolbox_item:write'])]
    public string $title = '';

    #[Groups(['toolbox_item:read', 'toolbox_item:write'])]
    public string $description = '';

    #[Groups(['toolbox_item:write'])]
    public string $prompt = '';

    #[Groups(['toolbox_item:write'])]
    public ?string $provider = null;

    #[Groups(['toolbox_item:read'])]
    public int $currentVersion = 1;

    #[Groups(['toolbox_item:read'])]
    public int $versionCount = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['toolbox_item:read'])]
    public array $versions = [];

    #[Groups(['toolbox_item:read'])]
    public bool $visible = false;

    #[Groups(['toolbox_item:read'])]
    public int $progress = 0;

    #[Groups(['toolbox_item:read'])]
    public ?float $score = null;

    #[Groups(['toolbox_item:read'])]
    public int $learningPathId = 0;

    #[Groups(['toolbox_item:read'])]
    public int $currentAttempt = 0;

    #[Groups(['toolbox_item:read'])]
    public bool $canRestart = false;

    #[Groups(['toolbox_item:read'])]
    public string $launchUrl = '';

    #[Groups(['toolbox_item:read'])]
    public string $previewUrl = '';

    #[Groups(['toolbox_item:read'])]
    public string $downloadUrl = '';

    #[Groups(['toolbox_item:read'])]
    public string $reportingUrl = '';

    #[Groups(['toolbox_item:read'])]
    public ?string $createdAt = null;

    #[Groups(['toolbox_item:read'])]
    public ?string $updatedAt = null;

    #[Groups(['toolbox_item:read'])]
    public bool $canEdit = false;

    #[Groups(['toolbox_item:read'])]
    public bool $canGenerate = false;
}
