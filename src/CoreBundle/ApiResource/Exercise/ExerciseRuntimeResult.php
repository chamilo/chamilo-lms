<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeResultProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeResult',
    operations: [
        new Get(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/result',
            requirements: [
                'exerciseId' => '\\d+',
                'attemptId' => '\\d+',
            ],
            openapi: new Operation(
                summary: 'Exercise runtime attempt result and review data',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_runtime_result',
            provider: ExerciseRuntimeResultProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_result:read']],
)]
final class ExerciseRuntimeResult
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_result:read'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_result:read'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_result:read'])]
    public string $title = '';

    #[Groups(['exercise_runtime_result:read'])]
    public string $description = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $attempt = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $visibility = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $questions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $ranking = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $legacyUrls = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $finalActions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime_result:read'])]
    public array $aiCorrection = [];

    #[Groups(['exercise_runtime_result:read'])]
    public bool $canManage = false;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
