<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeAnswerProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeAnswer',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/answer',
            requirements: [
                'exerciseId' => '\\d+',
                'attemptId' => '\\d+',
            ],
            openapi: new Operation(
                summary: 'Save a Vue exercise runtime draft answer',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'post_exercise_runtime_answer',
            processor: ExerciseRuntimeAnswerProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_answer:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_answer:write']],
)]
final class ExerciseRuntimeAnswer
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_answer:read', 'exercise_runtime_answer:write'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_answer:read', 'exercise_runtime_answer:write'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_answer:read', 'exercise_runtime_answer:write'])]
    public ?int $questionId = null;

    #[Groups(['exercise_runtime_answer:write'])]
    public mixed $answer = null;

    #[Groups(['exercise_runtime_answer:read', 'exercise_runtime_answer:write'])]
    public ?bool $reviewLater = null;

    #[Groups(['exercise_runtime_answer:write'])]
    public bool $reviewLaterOnly = false;

    #[Groups(['exercise_runtime_answer:read', 'exercise_runtime_answer:write'])]
    public int $secondsSpent = 0;

    #[Groups(['exercise_runtime_answer:write'])]
    public string $navigationAction = '';

    #[Groups(['exercise_runtime_answer:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_answer:read'])]
    public string $message = '';

    /**
     * @var array<int, array{answer: string, position: int|null}>
     */
    #[Groups(['exercise_runtime_answer:read'])]
    public array $savedAnswer = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_answer:read'])]
    public array $answeredQuestionIds = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_answer:read'])]
    public array $reviewQuestionIds = [];

    #[Groups(['exercise_runtime_answer:read'])]
    public int $answeredCount = 0;

    #[Groups(['exercise_runtime_answer:read'])]
    public bool $canFinish = false;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_runtime_answer:read'])]
    public array $feedback = [];

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
