<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntime',
    operations: [
        new Get(
            uriTemplate: '/exercise/runtime/{exerciseId}',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise runtime player data',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_runtime',
            provider: ExerciseRuntimeProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime:read']],
)]
final class ExerciseRuntime
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_runtime:read'])]
    public string $title = '';

    #[Groups(['exercise_runtime:read'])]
    public string $description = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime:read'])]
    public array $settings = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime:read'])]
    public array $questions = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_runtime:read'])]
    public array $legacyUrls = [];

    #[Groups(['exercise_runtime:read'])]
    public int $questionCount = 0;

    #[Groups(['exercise_runtime:read'])]
    public float $totalScore = 0.0;

    #[Groups(['exercise_runtime:read'])]
    public bool $canManage = false;


    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['exercise_runtime:read'])]
    public ?array $attempt = null;

    #[Groups(['exercise_runtime:read'])]
    public bool $canStartAttempt = false;

    #[Groups(['exercise_runtime:read'])]
    public bool $canSubmit = false;

    #[Groups(['exercise_runtime:read'])]
    public bool $usesLegacySubmit = true;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
