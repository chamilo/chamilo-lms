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
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionEditorProcessor;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionEditorProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestionEditor',
    operations: [
        new Get(
            uriTemplate: '/exercise/questions/{exerciseId}/editor',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise question editor for a new question',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'type', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_question_editor_create',
            provider: ExerciseQuestionEditorProvider::class,
        ),
        new Get(
            uriTemplate: '/exercise/questions/{exerciseId}/editor/{questionId}',
            requirements: ['exerciseId' => '\\d+', 'questionId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise question editor for an existing choice question',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_question_editor_edit',
            provider: ExerciseQuestionEditorProvider::class,
        ),
        new Post(
            uriTemplate: '/exercise/questions/{exerciseId}/editor',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Create an exercise question',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_question_editor_create',
            processor: ExerciseQuestionEditorProcessor::class,
        ),
        new Post(
            uriTemplate: '/exercise/questions/{exerciseId}/editor/{questionId}',
            requirements: ['exerciseId' => '\\d+', 'questionId' => '\\d+'],
            openapi: new Operation(
                summary: 'Update an exercise question',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_question_editor_update',
            processor: ExerciseQuestionEditorProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question_editor:read']],
    denormalizationContext: ['groups' => ['exercise_question_editor:write']],
)]
final class ExerciseQuestionEditor
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public ?int $questionId = null;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public int $type = 1;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $typeLabel = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $title = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $description = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $feedback = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $dropdownListText = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $fillBlanksText = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $fillBlankItems = [];

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public int $fillBlanksSeparator = 0;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public bool $fillBlanksSwitchable = false;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public bool $fillBlanksCaseInsensitive = false;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $fillBlanksComment = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $calculatedText = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $calculatedFormula = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $calculatedRanges = [];

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public int $calculatedVariations = 1;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $calculatedComment = '';

    #[Groups(['exercise_question_editor:read'])]
    public string $annotationImageUrl = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $annotationImageData = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $annotationImageName = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $annotationImageMimeType = '';

    #[Groups(['exercise_question_editor:read'])]
    public string $hotspotImageUrl = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotImageData = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotImageName = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotImageMimeType = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $hotspotItems = [];

    /**
     * @var array<int, array{label: string, value: string}>
     */
    #[Groups(['exercise_question_editor:read'])]
    public array $hotspotScenarioOptions = [];

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotScenarioSuccessType = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotScenarioSuccessUrl = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotScenarioFailureType = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $hotspotScenarioFailureUrl = '';

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public float $score = 0.0;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public float $globalScore = 0.0;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public float $correctScore = 1.0;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public float $wrongScore = -0.5;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public float $unknownScore = 0.0;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public bool $noNegativeScore = false;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public bool $mandatory = false;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public ?int $duration = null;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public int $difficulty = 1;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public int $categoryId = 0;

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public int $parentMediaId = 0;

    #[Groups(['exercise_question_editor:read'])]
    public int $questionCount = 0;

    #[Groups(['exercise_question_editor:read'])]
    public float $totalScore = 0.0;

    #[Groups(['exercise_question_editor:read'])]
    public bool $usesGlobalScore = false;

    #[Groups(['exercise_question_editor:read'])]
    public bool $hasFixedUnknownAnswer = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read'])]
    public array $categoryOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read'])]
    public array $mediaOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $answers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $matchingOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $matchingPairs = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public array $draggableItems = [];

    #[Groups(['exercise_question_editor:read', 'exercise_question_editor:write'])]
    public string $matchingOrientation = 'h';

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_question_editor:read'])]
    public array $legacyUrls = [];

    #[Groups(['exercise_question_editor:read'])]
    public string $csrfToken = '';

    #[Groups(['exercise_question_editor:read'])]
    public bool $allowQuestionFeedback = false;

    #[Groups(['exercise_question_editor:read'])]
    public bool $imageZoomEnabled = false;

    #[Groups(['exercise_question_editor:write'])]
    public string $submittedCsrfToken = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
