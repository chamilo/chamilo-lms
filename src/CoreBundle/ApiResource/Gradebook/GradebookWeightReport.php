<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookWeightReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookWeightReport',
    operations: [
        new Get(
            uriTemplate: '/gradebook/weights',
            openapi: new Operation(
                summary: 'Read Gradebook item weights for the current category',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_weights',
            provider: GradebookWeightReportProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_weight_report:read']],
)]
final class GradebookWeightReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_weight_report:read'])]
    public string $id = 'gradebook_weight_report';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_weight_report:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_weight_report:read'])]
    public ?array $category = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_weight_report:read'])]
    public array $items = [];

    #[Groups(['gradebook_weight_report:read'])]
    public float $expectedTotal = 0.0;

    #[Groups(['gradebook_weight_report:read'])]
    public float $currentTotal = 0.0;

    #[Groups(['gradebook_weight_report:read'])]
    public bool $canManage = false;

    #[Groups(['gradebook_weight_report:read'])]
    public bool $locked = false;

    #[Groups(['gradebook_weight_report:read'])]
    public string $csrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
