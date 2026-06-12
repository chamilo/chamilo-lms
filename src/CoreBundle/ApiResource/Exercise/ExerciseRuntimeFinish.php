<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeFinishProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeFinish',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/finish',
            requirements: [
                'exerciseId' => '\\d+',
                'attemptId' => '\\d+',
            ],
            openapi: new Operation(
                summary: 'Finish a Vue exercise runtime attempt using native migrated scoring',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'origin', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'learnpath_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'learnpath_item_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'learnpath_item_view_id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'post_exercise_runtime_finish',
            processor: ExerciseRuntimeFinishProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_finish:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_finish:write']],
)]
final class ExerciseRuntimeFinish
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_finish:read', 'exercise_runtime_finish:write'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_finish:read', 'exercise_runtime_finish:write'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_finish:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_finish:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_finish:read'])]
    public string $status = '';

    #[Groups(['exercise_runtime_finish:read'])]
    public float $score = 0.0;

    #[Groups(['exercise_runtime_finish:read'])]
    public float $maxScore = 0.0;

    #[Groups(['exercise_runtime_finish:read'])]
    public ?string $completedAt = null;

    #[Groups(['exercise_runtime_finish:read'])]
    public string $resultUrl = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
