<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\Controller\Api\LearningPathBuilderItemAction;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderMutationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Patch(
            uriTemplate: '/learning_path_builder_items/{itemId}',
            requirements: ['itemId' => '\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'update_learning_path_builder_item',
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Post(
            uriTemplate: '/learning_path_builder_items/{itemId}/edit',
            requirements: ['itemId' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            controller: LearningPathBuilderItemAction::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            output: false,
            read: false,
            deserialize: false,
            name: 'update_learning_path_builder_item_form',
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
            processor: LearningPathBuilderMutationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_item_update:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_item_update:write']],
)]
final class LearningPathBuilderItemUpdateInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_item_update:read'])]
    public ?int $itemId = null;

    #[Groups(['learning_path_builder_item_update:write'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder_item_update:read', 'learning_path_builder_item_update:write'])]
    public string $title = '';

    #[Groups(['learning_path_builder_item_update:read', 'learning_path_builder_item_update:write'])]
    public ?int $parentId = null;

    #[Groups(['learning_path_builder_item_update:read', 'learning_path_builder_item_update:write'])]
    public ?string $content = null;

    #[Groups(['learning_path_builder_item_update:read', 'learning_path_builder_item_update:write'])]
    public bool $exportAllowed = false;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['learning_path_builder_item_update:read', 'learning_path_builder_item_update:write'])]
    public array $extraFields = [];

    public function getItemId(): ?int
    {
        return $this->itemId;
    }
}
