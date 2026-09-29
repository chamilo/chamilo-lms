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
use Chamilo\CoreBundle\State\Gradebook\GradebookEvaluationResultActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookEvaluationResultAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/evaluation-results/action',
            openapi: new Operation(
                summary: 'Grade learners in a manual Gradebook evaluation',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER') or is_granted('ROLE_ADMIN')",
            name: 'post_gradebook_evaluation_result_action',
            processor: GradebookEvaluationResultActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_evaluation_result_action:read']],
    denormalizationContext: ['groups' => ['gradebook_evaluation_result_action:write']],
)]
final class GradebookEvaluationResultAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_evaluation_result_action:read'])]
    public string $id = 'gradebook_evaluation_result_action';

    #[Groups(['gradebook_evaluation_result_action:read', 'gradebook_evaluation_result_action:write'])]
    public string $action = '';

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public ?int $evaluationId = null;

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public ?int $userId = null;

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public ?int $resultId = null;

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public ?int $attemptId = null;

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public ?float $score = null;

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public string $comment = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_evaluation_result_action:write'])]
    public array $scores = [];

    #[Groups(['gradebook_evaluation_result_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_evaluation_result_action:read'])]
    public bool $success = false;

    #[Groups(['gradebook_evaluation_result_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
