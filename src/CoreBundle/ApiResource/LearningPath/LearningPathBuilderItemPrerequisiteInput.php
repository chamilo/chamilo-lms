<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderMutationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Put(
            uriTemplate: '/learning_path_builder_items/{itemId}/prerequisite',
            requirements: ['itemId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'update_learning_path_builder_item_prerequisite',
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_item_prerequisite:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_item_prerequisite:write']],
)]
final class LearningPathBuilderItemPrerequisiteInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_item_prerequisite:read'])]
    public ?int $itemId = null;

    #[Groups(['learning_path_builder_item_prerequisite:write'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder_item_prerequisite:read', 'learning_path_builder_item_prerequisite:write'])]
    public int $prerequisiteId = 0;

    #[Groups(['learning_path_builder_item_prerequisite:read', 'learning_path_builder_item_prerequisite:write'])]
    public float $minScore = 0.0;

    #[Groups(['learning_path_builder_item_prerequisite:read', 'learning_path_builder_item_prerequisite:write'])]
    public float $maxScore = 100.0;

    #[Groups(['learning_path_builder_item_prerequisite:write'])]
    public string $csrfToken = '';

    #[Groups(['learning_path_builder_item_prerequisite:read'])]
    public bool $saved = false;

    public function getItemId(): ?int
    {
        return $this->itemId;
    }
}
