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
            uriTemplate: '/learning_path_builder_items/{itemId}/audio',
            requirements: ['itemId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'update_learning_path_builder_item_audio',
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_item_audio:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_item_audio:write']],
)]
final class LearningPathBuilderItemAudioInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_item_audio:read'])]
    public ?int $itemId = null;

    #[Groups(['learning_path_builder_item_audio:write'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder_item_audio:read', 'learning_path_builder_item_audio:write'])]
    public ?int $documentId = null;

    #[Groups(['learning_path_builder_item_audio:write'])]
    public string $csrfToken = '';

    #[Groups(['learning_path_builder_item_audio:read'])]
    public bool $saved = false;

    public function getItemId(): ?int
    {
        return $this->itemId;
    }
}
