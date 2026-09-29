<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\GlobalReporting;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\GlobalReporting\GlobalReportingSectionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/global-reporting/report',
            name: 'get_global_reporting_section',
            parameters: [
                'section' => new QueryParameter(schema: ['type' => 'string'], required: true),
                'page' => new QueryParameter(schema: ['type' => 'integer']),
                'itemsPerPage' => new QueryParameter(schema: ['type' => 'integer']),
                'keyword' => new QueryParameter(schema: ['type' => 'string']),
                'sort' => new QueryParameter(schema: ['type' => 'string']),
                'direction' => new QueryParameter(schema: ['type' => 'string']),
                'status' => new QueryParameter(schema: ['type' => 'integer']),
                'active' => new QueryParameter(schema: ['type' => 'integer']),
                'sleepingDays' => new QueryParameter(schema: ['type' => 'integer']),
                'userId' => new QueryParameter(schema: ['type' => 'integer']),
                'courseId' => new QueryParameter(schema: ['type' => 'integer']),
                'sessionId' => new QueryParameter(schema: ['type' => 'integer']),
                'month' => new QueryParameter(schema: ['type' => 'integer', 'minimum' => 0, 'maximum' => 12]),
                'year' => new QueryParameter(schema: ['type' => 'string']),
                'exerciseId' => new QueryParameter(schema: ['type' => 'integer']),
                'score' => new QueryParameter(schema: ['type' => 'integer', 'minimum' => 0, 'maximum' => 100]),
                'startDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'endDate' => new QueryParameter(schema: ['type' => 'string', 'format' => 'date']),
                'mode' => new QueryParameter(schema: ['type' => 'string']),
                'language' => new QueryParameter(schema: ['type' => 'string']),
            ],
            provider: GlobalReportingSectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['global_reporting_section:read']],
)]
final class GlobalReportingSection
{
    #[ApiProperty(identifier: true)]
    #[Groups(['global_reporting_section:read'])]
    public string $id = 'global_reporting_section';

    #[Groups(['global_reporting_section:read'])]
    public string $section = '';

    #[Groups(['global_reporting_section:read'])]
    public string $title = '';

    #[Groups(['global_reporting_section:read'])]
    public int $total = 0;

    #[Groups(['global_reporting_section:read'])]
    public int $page = 1;

    #[Groups(['global_reporting_section:read'])]
    public int $itemsPerPage = 20;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['global_reporting_section:read'])]
    public array $summary = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['global_reporting_section:read'])]
    public array $columns = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['global_reporting_section:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['global_reporting_section:read'])]
    public array $sections = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['global_reporting_section:read'])]
    public array $meta = [];
}
