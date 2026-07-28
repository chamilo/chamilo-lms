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
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionImportProcessor;
use Chamilo\CoreBundle\State\Exercise\ExerciseQuestionImportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseQuestionImport',
    operations: [
        new Get(
            uriTemplate: '/exercise/import/{importType}',
            requirements: ['importType' => 'aiken|excel|qti2'],
            openapi: new Operation(
                summary: 'Exercise question import data',
                parameters: [
                    new Parameter(name: 'importType', in: 'path', required: true, schema: ['type' => 'string']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'origin', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'returnToLp', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lp_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'learnpath_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'lp_parent_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'parent', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_question_import',
            provider: ExerciseQuestionImportProvider::class,
        ),
        new Post(
            uriTemplate: '/exercise/import/{importType}',
            requirements: ['importType' => 'aiken|excel|qti2'],
            openapi: new Operation(
                summary: 'Import exercise questions',
                parameters: [
                    new Parameter(name: 'importType', in: 'path', required: true, schema: ['type' => 'string']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'origin', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'returnToLp', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lp_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'learnpath_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'lp_parent_id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'parent', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'submittedCsrfToken' => ['type' => 'string'],
                                    'totalWeight' => ['type' => 'number'],
                                    'useCustomScore' => ['type' => 'boolean'],
                                    'correctScore' => ['type' => 'number'],
                                    'incorrectScore' => ['type' => 'number'],
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'Aiken .txt/.zip file, Excel .xls/.xlsx file or QTI2 .zip file',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            deserialize: false,
            name: 'post_exercise_question_import',
            processor: ExerciseQuestionImportProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_question_import:read']],
)]
final class ExerciseQuestionImport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_question_import:read'])]
    public string $importType = 'aiken';

    #[Groups(['exercise_question_import:read'])]
    public string $title = '';

    #[Groups(['exercise_question_import:read'])]
    public string $csrfToken = '';

    #[Groups(['exercise_question_import:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_question_import:read'])]
    public bool $success = false;

    #[Groups(['exercise_question_import:read'])]
    public string $message = '';

    #[Groups(['exercise_question_import:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_question_import:read'])]
    public string $exerciseTitle = '';

    #[Groups(['exercise_question_import:read'])]
    public int $importedQuestionCount = 0;

    #[Groups(['exercise_question_import:read'])]
    public int $skippedQuestionCount = 0;

    /**
     * @var array<int, string>
     */
    #[Groups(['exercise_question_import:read'])]
    public array $errors = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_question_import:read'])]
    public array $actionUrls = [];

    #[Groups(['exercise_question_import:read'])]
    public string $sample = '';

    #[Groups(['exercise_question_import:read'])]
    public bool $learningPathContext = false;

    #[Groups(['exercise_question_import:read'])]
    public ?int $learningPathItemId = null;

    #[Groups(['exercise_question_import:read'])]
    public string $learningPathMessage = '';

    public function getImportType(): string
    {
        return $this->importType;
    }
}
