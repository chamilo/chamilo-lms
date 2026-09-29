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
use Chamilo\CoreBundle\State\Gradebook\GradebookScoringActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookScoringAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/scoring-settings/action',
            openapi: new Operation(
                summary: 'Update Gradebook score display settings for the current category',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_scoring_settings_action',
            processor: GradebookScoringActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_scoring_action:read']],
    denormalizationContext: ['groups' => ['gradebook_scoring_action:write']],
)]
final class GradebookScoringAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_scoring_action:read'])]
    public string $id = 'gradebook_scoring_action';

    #[Groups(['gradebook_scoring_action:write'])]
    public ?int $categoryId = null;

    #[Groups(['gradebook_scoring_action:write'])]
    public ?int $colorSplitPercent = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_scoring_action:write'])]
    public array $ranges = [];

    #[Groups(['gradebook_scoring_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_scoring_action:read'])]
    public bool $success = false;

    public function getId(): string
    {
        return $this->id;
    }
}
