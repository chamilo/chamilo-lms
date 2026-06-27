<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderMutationProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_path_builder_items/{itemId}/delete',
            requirements: ['itemId' => '\\d+'],
            read: false,
            name: 'delete_learning_path_builder_item',
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_delete:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_delete:write']],
)]
final class LearningPathBuilderDeleteInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_delete:read'])]
    public ?int $itemId = null;

    #[Groups(['learning_path_builder_delete:write'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder_delete:write'])]
    public string $csrfToken = '';

    #[Groups(['learning_path_builder_delete:read'])]
    public bool $deleted = false;

    public function getItemId(): ?int
    {
        return $this->itemId;
    }
}
