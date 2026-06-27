<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathAiGeneratorProcessor;
use Chamilo\CoreBundle\State\LearningPath\LearningPathAiGeneratorProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/ai-generator',
            name: 'get_learning_path_ai_generator',
            provider: LearningPathAiGeneratorProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Post(
            uriTemplate: '/learning_paths/ai-generator',
            name: 'create_learning_path_from_ai',
            read: false,
            processor: LearningPathAiGeneratorProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_ai_generator:read']],
    denormalizationContext: ['groups' => ['learning_path_ai_generator:write']],
)]
final class LearningPathAiGenerator
{
    #[Groups(['learning_path_ai_generator:read'])]
    public bool $enabled = false;

    #[Groups(['learning_path_ai_generator:read'])]
    public string $language = 'en';

    /** @var array<int, array{label: string, value: string}> */
    #[Groups(['learning_path_ai_generator:read'])]
    public array $providers = [];

    #[Groups(['learning_path_ai_generator:read', 'learning_path_ai_generator:write'])]
    public string $csrfToken = '';

    /** @var array<string, mixed> */
    #[Groups(['learning_path_ai_generator:write'])]
    public array $lpData = [];

    #[Groups(['learning_path_ai_generator:read'])]
    public ?int $id = null;

    #[Groups(['learning_path_ai_generator:read'])]
    public string $title = '';
}
