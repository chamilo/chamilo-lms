<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathQuickTestProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_path_builder_items/{itemId}/quick-test',
            requirements: ['itemId' => '\\d+'],
            read: false,
            name: 'create_learning_path_builder_quick_test',
            processor: LearningPathQuickTestProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_quick_test:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_quick_test:write']],
)]
final class LearningPathBuilderQuickTestInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_quick_test:read'])]
    public ?int $itemId = null;

    #[Groups(['learning_path_builder_quick_test:write'])]
    public int $lpId = 0;

    #[Groups(['learning_path_builder_quick_test:write'])]
    public string $csrfToken = '';

    #[Groups(['learning_path_builder_quick_test:read', 'learning_path_builder_quick_test:write'])]
    public string $provider = '';

    #[Groups(['learning_path_builder_quick_test:read'])]
    public ?int $exerciseId = null;

    #[Groups(['learning_path_builder_quick_test:read'])]
    public string $title = '';

    #[Groups(['learning_path_builder_quick_test:read'])]
    public bool $created = false;
}
