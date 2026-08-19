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
use Chamilo\CoreBundle\State\Gradebook\GradebookReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookReport',
    operations: [
        new Get(
            uriTemplate: '/gradebook/report',
            openapi: new Operation(
                summary: 'Read-only Gradebook learner score report for the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'includeScores', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'all', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'sortBy', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'sortDirection', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_report',
            provider: GradebookReportProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_report:read']],
)]
final class GradebookReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_report:read'])]
    public string $id = 'gradebook_report';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_report:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_report:read'])]
    public ?array $category = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_report:read'])]
    public array $columns = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_report:read'])]
    public array $extraFieldColumns = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_report:read'])]
    public array $rows = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_report:read'])]
    public array $settings = [];

    #[Groups(['gradebook_report:read'])]
    public string $commentCsrfToken = '';

    #[Groups(['gradebook_report:read'])]
    public int $page = 1;

    #[Groups(['gradebook_report:read'])]
    public int $itemsPerPage = 20;

    #[Groups(['gradebook_report:read'])]
    public int $totalItems = 0;

    #[Groups(['gradebook_report:read'])]
    public string $sortBy = 'fullName';

    #[Groups(['gradebook_report:read'])]
    public string $sortDirection = 'asc';

    public function getId(): string
    {
        return $this->id;
    }
}
