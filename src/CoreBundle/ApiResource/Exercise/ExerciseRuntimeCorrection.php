<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeCorrectionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeCorrection',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/correction',
            requirements: [
                'exerciseId' => '\\d+',
                'attemptId' => '\\d+',
            ],
            openapi: new Operation(
                summary: 'Correct a manual Vue exercise runtime answer',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_runtime_correction',
            processor: ExerciseRuntimeCorrectionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_correction:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_correction:write']],
)]
final class ExerciseRuntimeCorrection
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_correction:read', 'exercise_runtime_correction:write'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_correction:read', 'exercise_runtime_correction:write'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_correction:read', 'exercise_runtime_correction:write'])]
    public ?int $questionId = null;

    #[Groups(['exercise_runtime_correction:read', 'exercise_runtime_correction:write'])]
    public float $marks = 0.0;

    #[Groups(['exercise_runtime_correction:read', 'exercise_runtime_correction:write'])]
    public string $teacherComment = '';

    #[Groups(['exercise_runtime_correction:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_correction:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_correction:read'])]
    public float $score = 0.0;

    #[Groups(['exercise_runtime_correction:read'])]
    public float $maxScore = 0.0;

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_correction:read'])]
    public array $questionsToCheck = [];

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
