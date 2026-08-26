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
use Chamilo\CoreBundle\State\Gradebook\GradebookEvaluationActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookEvaluationAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/evaluations/action',
            openapi: new Operation(
                summary: 'Run a manual Gradebook evaluation action in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_evaluation_action',
            processor: GradebookEvaluationActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_evaluation_action:read']],
    denormalizationContext: ['groups' => ['gradebook_evaluation_action:write']],
)]
final class GradebookEvaluationAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_evaluation_action:read'])]
    public string $id = 'gradebook_evaluation_action';

    #[Groups(['gradebook_evaluation_action:read', 'gradebook_evaluation_action:write'])]
    public string $action = '';

    #[Groups(['gradebook_evaluation_action:read', 'gradebook_evaluation_action:write'])]
    public ?int $evaluationId = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public ?int $categoryId = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public ?int $targetCategoryId = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public string $title = '';

    #[Groups(['gradebook_evaluation_action:write'])]
    public string $description = '';

    #[Groups(['gradebook_evaluation_action:write'])]
    public ?float $weight = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public ?float $maxScore = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public ?float $minScore = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public ?bool $visible = null;

    #[Groups(['gradebook_evaluation_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_evaluation_action:read'])]
    public bool $success = false;

    #[Groups(['gradebook_evaluation_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
