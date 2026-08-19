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
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookLinkAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/links/action',
            openapi: new Operation(
                summary: 'Run a Gradebook online activity link action in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_link_action',
            processor: GradebookLinkActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_link_action:read']],
    denormalizationContext: ['groups' => ['gradebook_link_action:write']],
)]
final class GradebookLinkAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_link_action:read'])]
    public string $id = 'gradebook_link_action';

    #[Groups(['gradebook_link_action:read', 'gradebook_link_action:write'])]
    public string $action = '';

    #[Groups(['gradebook_link_action:read', 'gradebook_link_action:write'])]
    public ?int $linkId = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?int $categoryId = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?int $targetCategoryId = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?int $type = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?int $refId = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?float $weight = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?float $minScore = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?float $pointsOne = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?float $pointsMany = null;

    #[Groups(['gradebook_link_action:write'])]
    public ?bool $visible = null;

    #[Groups(['gradebook_link_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_link_action:read'])]
    public bool $success = false;

    #[Groups(['gradebook_link_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
