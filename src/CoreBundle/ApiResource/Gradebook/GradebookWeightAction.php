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
use Chamilo\CoreBundle\State\Gradebook\GradebookWeightActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookWeightAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/weights/action',
            openapi: new Operation(
                summary: 'Update or automatically distribute Gradebook item weights',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_weight_action',
            processor: GradebookWeightActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_weight_action:read']],
    denormalizationContext: ['groups' => ['gradebook_weight_action:write']],
)]
final class GradebookWeightAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_weight_action:read'])]
    public string $id = 'gradebook_weight_action';

    #[Groups(['gradebook_weight_action:read', 'gradebook_weight_action:write'])]
    public string $action = '';

    #[Groups(['gradebook_weight_action:write'])]
    public ?int $categoryId = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_weight_action:write'])]
    public array $weights = [];

    #[Groups(['gradebook_weight_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_weight_action:read'])]
    public bool $success = false;

    public function getId(): string
    {
        return $this->id;
    }
}
