<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeAttemptProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeAttempt',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Start or resume an exercise runtime attempt',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'origin', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'learnpath_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'learnpath_item_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'learnpath_item_view_id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_runtime_attempt',
            processor: ExerciseRuntimeAttemptProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_attempt:read']],
    denormalizationContext: ['groups' => ['exercise_runtime_attempt:write']],
)]
final class ExerciseRuntimeAttempt
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_attempt:read', 'exercise_runtime_attempt:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_runtime_attempt:read'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_attempt:read'])]
    public ?int $attemptNumber = null;

    #[Groups(['exercise_runtime_attempt:read'])]
    public string $status = '';

    #[Groups(['exercise_runtime_attempt:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_attempt:read'])]
    public bool $preview = false;

    #[Groups(['exercise_runtime_attempt:read'])]
    public bool $usesLegacyRuntime = false;

    #[Groups(['exercise_runtime_attempt:read'])]
    public string $message = '';

    #[Groups(['exercise_runtime_attempt:read'])]
    public int $currentQuestionIndex = 0;

    #[Groups(['exercise_runtime_attempt:read'])]
    public ?int $currentQuestionId = null;

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_attempt:read'])]
    public array $questionIds = [];

    #[Groups(['exercise_runtime_attempt:read'])]
    public int $totalQuestions = 0;

    #[Groups(['exercise_runtime_attempt:read'])]
    public ?string $startedAt = null;

    #[Groups(['exercise_runtime_attempt:read'])]
    public ?string $expiredAt = null;

    #[Groups(['exercise_runtime_attempt:read'])]
    public ?int $remainingSeconds = null;

    #[Groups(['exercise_runtime_attempt:read'])]
    public bool $canNavigatePrevious = false;

    #[Groups(['exercise_runtime_attempt:read'])]
    public bool $canNavigateNext = false;

    #[Groups(['exercise_runtime_attempt:read'])]
    public bool $canFinish = false;

    /**
     * @var array<int|string, array<int, array{answer: string, position: int|null}>>
     */
    #[Groups(['exercise_runtime_attempt:read'])]
    public array $savedAnswers = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_runtime_attempt:read'])]
    public array $legacyUrls = [];

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
