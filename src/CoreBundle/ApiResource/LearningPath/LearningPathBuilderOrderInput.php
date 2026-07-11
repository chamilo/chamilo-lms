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
            uriTemplate: '/learning_paths/{lpId}/builder/reorder',
            requirements: ['lpId' => '\\d+'],
            read: false,
            name: 'reorder_learning_path_builder_items',
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_order:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_order:write']],
)]
final class LearningPathBuilderOrderInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_order:read'])]
    public ?int $lpId = null;

    /** @var array<int, array{id:int, parentId:int|null}> */
    #[Groups(['learning_path_builder_order:write'])]
    public array $order = [];

    #[Groups(['learning_path_builder_order:write'])]
    public string $csrfToken = '';

    #[Groups(['learning_path_builder_order:read'])]
    public bool $saved = false;

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
