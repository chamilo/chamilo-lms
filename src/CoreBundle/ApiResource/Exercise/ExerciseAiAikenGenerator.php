<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseAiAikenGeneratorProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseAiAikenGenerator',
    operations: [
        new Get(
            uriTemplate: '/exercise/ai-aiken-generator',
            openapi: new Operation(
                summary: 'Exercise AI Aiken generator data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_ai_aiken_generator',
            provider: ExerciseAiAikenGeneratorProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_ai_aiken_generator:read']],
)]
final class ExerciseAiAikenGenerator
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_ai_aiken_generator:read'])]
    public string $id = 'exercise_ai_aiken_generator';

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public bool $enabled = false;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public bool $courseExerciseGeneratorEnabled = false;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public bool $aiHelpersEnabled = false;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public string $language = 'en';

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public string $csrfToken = '';

    /**
     * @var array<int, array{value: string, label: string}>
     */
    #[Groups(['exercise_ai_aiken_generator:read'])]
    public array $textProviders = [];

    /**
     * @var array<int, array{value: string, label: string}>
     */
    #[Groups(['exercise_ai_aiken_generator:read'])]
    public array $documentProviders = [];

    /**
     * @var array<int, array{value: string, label: string}>
     */
    #[Groups(['exercise_ai_aiken_generator:read'])]
    public array $questionTypes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_ai_aiken_generator:read'])]
    public array $documents = [];

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public int $defaultNumberOfQuestions = 10;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public int $maxNumberOfQuestions = 100;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public int $defaultTotalWeight = 20;

    #[Groups(['exercise_ai_aiken_generator:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
