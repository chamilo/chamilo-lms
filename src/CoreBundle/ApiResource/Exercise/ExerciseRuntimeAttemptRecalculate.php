<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeAttemptRecalculateProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeAttemptRecalculate',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/recalculate',
            requirements: [
                'exerciseId' => '\\d+',
                'attemptId' => '\\d+',
            ],
            openapi: new Operation(
                summary: 'Recalculate an exercise attempt score from the migrated report',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_runtime_attempt_recalculate',
            processor: ExerciseRuntimeAttemptRecalculateProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_attempt_recalculate:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_attempt_recalculate:write']],
)]
final class ExerciseRuntimeAttemptRecalculate
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_attempt_recalculate:read', 'exercise_runtime_attempt_recalculate:write'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_attempt_recalculate:read', 'exercise_runtime_attempt_recalculate:write'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_attempt_recalculate:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_attempt_recalculate:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_attempt_recalculate:read'])]
    public float $score = 0.0;

    #[Groups(['exercise_runtime_attempt_recalculate:read'])]
    public float $maxScore = 0.0;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
