<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeQuestionGradingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Lists the answers that all learners gave to one manually graded question of an exercise, so a
 * teacher can score that question across attempts without opening each attempt.
 */
#[ApiResource(
    shortName: 'ExerciseRuntimeQuestionGrading',
    operations: [
        new Get(
            uriTemplate: '/exercise/runtime/{exerciseId}/question-grading',
            requirements: ['exerciseId' => '\d+'],
            openapi: new Operation(
                summary: 'Answers of all learners to one manually graded question of an exercise',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_runtime_question_grading',
            provider: ExerciseRuntimeQuestionGradingProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
                'questionId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Question to grade; defaults to the first manually graded question',
                ),
                'sort' => new QueryParameter(
                    schema: ['type' => 'string', 'enum' => ['date', 'lastname']],
                    description: 'Order by submission date (default) or by learner last name',
                ),
                'onlyUnscored' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    description: 'Only list the answers that no teacher scored yet',
                ),
                'page' => new QueryParameter(
                    schema: ['type' => 'integer', 'minimum' => 1],
                    description: 'Page number',
                ),
                'itemsPerPage' => new QueryParameter(
                    schema: ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                    description: 'Answers per page',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_question_grading:read']],
)]
final class ExerciseRuntimeQuestionGrading
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_question_grading:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_runtime_question_grading:read'])]
    public string $title = '';

    /**
     * The manually graded questions of the exercise, with how many answers still wait for a score.
     *
     * @var array<int, array{id: int, title: string, type: int, maxScore: float, unscoredCount: int}>
     */
    #[Groups(['exercise_runtime_question_grading:read'])]
    public array $questions = [];

    #[Groups(['exercise_runtime_question_grading:read'])]
    public ?int $questionId = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_runtime_question_grading:read'])]
    public array $items = [];

    #[Groups(['exercise_runtime_question_grading:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_runtime_question_grading:read'])]
    public int $page = 1;

    #[Groups(['exercise_runtime_question_grading:read'])]
    public int $itemsPerPage = 20;

    #[Groups(['exercise_runtime_question_grading:read'])]
    public string $sort = 'date';

    #[Groups(['exercise_runtime_question_grading:read'])]
    public bool $onlyUnscored = false;

    #[Groups(['exercise_runtime_question_grading:read'])]
    public bool $lockedByGradebook = false;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
