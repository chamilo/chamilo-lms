<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookEvaluationResultsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookEvaluationResults',
    operations: [
        new Get(
            uriTemplate: '/gradebook/evaluation-results',
            openapi: new Operation(
                summary: 'Manual Gradebook evaluation results for the current course context',
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_ADMIN')",
            name: 'get_gradebook_evaluation_results',
            provider: GradebookEvaluationResultsProvider::class,
            // Declared here rather than in the openapi operation: a QueryParameter
            // documents AND validates, while an openapi Parameter only documents,
            // and a twin declaration there would hide this one.
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier. The group must belong to the course',
                ),
                'node' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Resource node of the course, which the provider checks against the course itself',
                    required: true,
                ),
                'evaluationId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Manual evaluation to read, which must belong to this course Gradebook',
                    required: true,
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_evaluation_results:read']],
)]
final class GradebookEvaluationResults
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_evaluation_results:read'])]
    public string $id = 'gradebook_evaluation_results';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_evaluation_results:read'])]
    public array $evaluation = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_evaluation_results:read'])]
    public array $results = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_evaluation_results:read'])]
    public array $scoreOptions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_evaluation_results:read'])]
    public array $settings = [];

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_evaluation_results:read'])]
    public array $context = [];

    #[Groups(['gradebook_evaluation_results:read'])]
    public string $csrfToken = '';

    #[Groups(['gradebook_evaluation_results:read'])]
    public string $importCsrfToken = '';

    #[Groups(['gradebook_evaluation_results:read'])]
    public bool $canManage = false;

    public function getId(): string
    {
        return $this->id;
    }
}
