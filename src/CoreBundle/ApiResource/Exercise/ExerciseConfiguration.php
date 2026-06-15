<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseConfigurationProcessor;
use Chamilo\CoreBundle\State\Exercise\ExerciseConfigurationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseConfiguration',
    operations: [
        new Get(
            uriTemplate: '/exercise/configuration',
            openapi: new Operation(
                summary: 'Exercise configuration defaults for creation',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_configuration_create',
            provider: ExerciseConfigurationProvider::class,
        ),
        new Get(
            uriTemplate: '/exercise/configuration/{exerciseId}',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise configuration for edition',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_configuration_edit',
            provider: ExerciseConfigurationProvider::class,
        ),
        new Post(
            uriTemplate: '/exercise/configuration',
            openapi: new Operation(
                summary: 'Create an exercise configuration',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_configuration',
            processor: ExerciseConfigurationProcessor::class,
        ),
        new Put(
            uriTemplate: '/exercise/configuration/{exerciseId}',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Update an exercise configuration',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            read: false,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'put_exercise_configuration',
            processor: ExerciseConfigurationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_configuration:read']],
    denormalizationContext: ['groups' => ['exercise_configuration:write']],
)]
final class ExerciseConfiguration
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_configuration:read'])]
    public string $mode = 'create';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $title = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $description = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $type = 2;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public ?int $categoryId = null;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $language = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $updateTitleInLearningPaths = false;

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public array $skillIds = [];

    /**
     * Legacy extra field values keyed by extra field variable.
     *
     * @var array<string, mixed>
     */
    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public array $extraFieldValues = [];

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $extraNotification = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public ?string $startTime = null;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public ?string $endTime = null;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public ?int $duration = null;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $maxAttempt = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $passPercentage = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $random = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $randomByCategory = 0;

    /**
     * Legacy category matrix stored in c_quiz_rel_category.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public array $categoryMatrix = [];

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $randomAnswers = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $showPreviousButton = true;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $preventBackwards = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $hideAttemptsTable = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $autoLaunch = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $addToGradebook = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public ?int $gradebookCategoryId = null;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $gradebookWeight = 100;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $gradebookVisible = true;

    /**
     * Legacy comma-separated notification ids stored as an array for Vue.
     *
     * @var array<int, int>
     */
    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public array $notifications = [];

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $accessCondition = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $sound = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $feedbackType = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $resultsDisabled = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $questionSelectionType = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $displayCategoryName = true;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $hideQuestionTitle = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $hideQuestionNumber = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $propagateNeg = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $saveCorrectAnswers = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public bool $reviewAnswers = false;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $expiredTime = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $displayChartDegreeCertainty = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $sendEmailChartDegreeCertainty = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $notDisplayBalancePercentageCategorieQuestion = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $displayChartDegreeCertaintyCategory = 0;

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public int $gatherQuestionsCategories = 0;

    /**
     * @var array<string, bool>
     */
    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public array $pageResultConfiguration = [];

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $textWhenFinished = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $textWhenFinishedFailure = '';

    #[Groups(['exercise_configuration:read', 'exercise_configuration:write'])]
    public string $csrfToken = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_configuration:read'])]
    public array $settings = [];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    #[Groups(['exercise_configuration:read'])]
    public array $options = [];

    #[Groups(['exercise_configuration:read'])]
    public bool $canEdit = false;

    /**
     * Legacy freezes these fields after an exercise has been created.
     *
     * @var array<int, string>
     */
    #[Groups(['exercise_configuration:read'])]
    public array $lockedFields = [];

    #[Groups(['exercise_configuration:read'])]
    public bool $canCreate = false;

    #[Groups(['exercise_configuration:read'])]
    public string $listUrl = '';

    #[Groups(['exercise_configuration:read'])]
    public string $questionsUrl = '';
}
