<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookReport',
    operations: [
        new Get(
            uriTemplate: '/gradebook/report',
            openapi: new Operation(
                summary: 'Read-only Gradebook learner score report for the current course context',
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_report',
            provider: GradebookReportProvider::class,
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
                'categoryId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Gradebook category to report on. Defaults to the root category of the course',
                ),
                'page' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Page to return, from 1. Ignored when "all" is true',
                ),
                'itemsPerPage' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Learners per page, from 1 to 100, 20 by default. Ignored when "all" is true',
                ),
                'search' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'Filters the learners by name, login or code',
                ),
                'sortBy' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'One of fullName, firstName, lastName or username. Anything else sorts by fullName',
                ),
                'sortDirection' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'Either asc or desc. Anything else sorts ascending',
                ),
                'all' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    description: 'Returns every learner in one page, for an export',
                ),
                'includeScores' => new QueryParameter(
                    schema: ['type' => 'boolean'],
                    description: 'False returns the learners without the score columns, which is much cheaper',
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
