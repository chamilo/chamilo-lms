<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionBankProcessor;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionBankProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestionBank',
    operations: [
        new Get(
            uriTemplate: '/exercise/questions/bank',
            openapi: new Operation(
                summary: 'Global exercise question bank data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sourceExerciseId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'difficulty', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionType', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_global_question_bank',
            provider: ExerciseQuestionBankProvider::class,
        ),
        new Get(
            uriTemplate: '/exercise/questions/{exerciseId}/bank',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise question bank data',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sourceExerciseId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'difficulty', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionType', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_question_bank',
            provider: ExerciseQuestionBankProvider::class,
        ),
        new Post(
            uriTemplate: '/exercise/questions/bank/action',
            openapi: new Operation(
                summary: 'Run a global exercise question bank action',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_global_question_bank_action',
            processor: ExerciseQuestionBankProcessor::class,
        ),
        new Post(
            uriTemplate: '/exercise/questions/{exerciseId}/bank/action',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Run an exercise question bank action',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_question_bank_action',
            processor: ExerciseQuestionBankProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question_bank:read']],
    denormalizationContext: ['groups' => ['exercise_question_bank:write']],
)]
final class ExerciseQuestionBank
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question_bank:read', 'exercise_question_bank:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_question_bank:read', 'exercise_question_bank:write'])]
    public string $action = '';

    #[Groups(['exercise_question_bank:read', 'exercise_question_bank:write'])]
    public ?int $questionId = null;

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_question_bank:read', 'exercise_question_bank:write'])]
    public array $questionIds = [];

    #[Groups(['exercise_question_bank:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['exercise_question_bank:read'])]
    public string $title = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $categoryOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $exerciseOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $difficultyOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $questionTypeOptions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $filters = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_question_bank:read'])]
    public array $legacyUrls = [];

    #[Groups(['exercise_question_bank:read'])]
    public int $page = 1;

    #[Groups(['exercise_question_bank:read'])]
    public int $itemsPerPage = 20;

    #[Groups(['exercise_question_bank:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_question_bank:read'])]
    public string $csrfToken = '';

    #[Groups(['exercise_question_bank:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_question_bank:read'])]
    public bool $globalMode = false;

    #[Groups(['exercise_question_bank:read'])]
    public bool $canDelete = false;

    #[Groups(['exercise_question_bank:read'])]
    public bool $success = false;

    #[Groups(['exercise_question_bank:read'])]
    public int $addedCount = 0;

    #[Groups(['exercise_question_bank:read'])]
    public int $skippedCount = 0;

    #[Groups(['exercise_question_bank:read'])]
    public string $message = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
