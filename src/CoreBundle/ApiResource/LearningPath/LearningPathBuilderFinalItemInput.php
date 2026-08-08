<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderMutationProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/builder/final-item',
            requirements: ['lpId' => '\d+'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            read: false,
            name: 'save_learning_path_builder_final_item',
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
    normalizationContext: ['groups' => ['learning_path_builder_final_item:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_final_item:write']],
)]
final class LearningPathBuilderFinalItemInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_final_item:read'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder_final_item:write'])]
    public int $documentId = 0;

    #[Groups(['learning_path_builder_final_item:read', 'learning_path_builder_final_item:write'])]
    public string $title = '';

    #[Groups(['learning_path_builder_final_item:read', 'learning_path_builder_final_item:write'])]
    public ?int $gradebookCategoryId = null;

    #[ApiProperty(identifier: false)]
    #[Groups(['learning_path_builder_final_item:read'])]
    public ?int $itemId = null;

    #[Groups(['learning_path_builder_final_item:read'])]
    public bool $saved = false;

    public function getLpId(): int
    {
        return $this->lpId;
    }
}
