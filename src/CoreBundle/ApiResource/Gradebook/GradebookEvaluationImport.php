<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\State\Gradebook\GradebookEvaluationImportProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookEvaluationImport',
    operations: [
        new Post(
            uriTemplate: '/gradebook/evaluation-results/import',
            openapi: new Operation(
                summary: 'Import manual Gradebook evaluation results from CSV',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['file', 'evaluationId', 'submittedCsrfToken'],
                                'properties' => [
                                    'file' => ['type' => 'string', 'format' => 'binary'],
                                    'evaluationId' => ['type' => 'integer'],
                                    'overwrite' => ['type' => 'boolean'],
                                    'ignoreErrors' => ['type' => 'boolean'],
                                    'submittedCsrfToken' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            inputFormats: ['multipart' => ['multipart/form-data']],
            deserialize: false,
            name: 'post_gradebook_evaluation_results_import',
            processor: GradebookEvaluationImportProcessor::class,
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
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_evaluation_import:read']],
)]
final class GradebookEvaluationImport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_evaluation_import:read'])]
    public string $id = 'gradebook_evaluation_import';

    #[Groups(['gradebook_evaluation_import:read'])]
    public bool $success = false;

    #[Groups(['gradebook_evaluation_import:read'])]
    public int $addedCount = 0;

    #[Groups(['gradebook_evaluation_import:read'])]
    public int $overwrittenCount = 0;

    #[Groups(['gradebook_evaluation_import:read'])]
    public int $unchangedCount = 0;

    #[Groups(['gradebook_evaluation_import:read'])]
    public int $skippedCount = 0;

    #[Groups(['gradebook_evaluation_import:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
