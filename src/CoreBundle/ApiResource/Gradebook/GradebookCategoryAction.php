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
use Chamilo\CoreBundle\State\Gradebook\GradebookCategoryActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCategoryAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/categories/action',
            openapi: new Operation(
                summary: 'Run a Gradebook category action in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_category_action',
            processor: GradebookCategoryActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_category_action:read']],
    denormalizationContext: ['groups' => ['gradebook_category_action:write']],
)]
final class GradebookCategoryAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_category_action:read'])]
    public string $id = 'gradebook_category_action';

    #[Groups(['gradebook_category_action:read', 'gradebook_category_action:write'])]
    public string $action = '';

    #[Groups(['gradebook_category_action:read', 'gradebook_category_action:write'])]
    public ?int $categoryId = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?int $parentCategoryId = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?int $targetCategoryId = null;

    #[Groups(['gradebook_category_action:write'])]
    public string $title = '';

    #[Groups(['gradebook_category_action:write'])]
    public string $description = '';

    #[Groups(['gradebook_category_action:write'])]
    public ?float $weight = null;

    #[Groups(['gradebook_category_action:write'])]
    public string $calculationMode = '';

    #[Groups(['gradebook_category_action:write'])]
    public ?bool $visible = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?int $certificateMinScore = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?bool $generateCertificates = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?bool $isRequirement = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?bool $allowSkillsBySubcategory = null;

    #[Groups(['gradebook_category_action:write'])]
    public ?int $gradeModelId = null;

    /**
     * @var list<int>|null
     */
    #[Groups(['gradebook_category_action:write'])]
    public ?array $skillIds = null;

    #[Groups(['gradebook_category_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_category_action:read'])]
    public bool $success = false;

    #[Groups(['gradebook_category_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
