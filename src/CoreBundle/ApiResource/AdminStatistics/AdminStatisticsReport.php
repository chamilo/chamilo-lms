<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\AdminStatistics;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\AdminStatistics\AdminStatisticsReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminStatisticsReport',
    operations: [
        new Get(
            uriTemplate: '/admin/statistics/report',
            openapi: new Operation(
                summary: 'Read an administration statistics report',
                parameters: [
                    new Parameter(name: 'report', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'dateDiff', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'column', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'direction', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'toolIds', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'rangeStart', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'rangeEnd', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'statusId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sessionDuration', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'month', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'section', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'ceiling', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'activeOnly', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'sortField', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'sortOrder', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'dupMode', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'extraFieldId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'additionalProfileFields', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_admin_statistics_report',
            provider: AdminStatisticsReportProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['admin_statistics_report:read']],
)]
final class AdminStatisticsReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['admin_statistics_report:read'])]
    public string $id = 'admin_statistics_report';

    #[Groups(['admin_statistics_report:read'])]
    public string $report = 'courses';

    #[Groups(['admin_statistics_report:read'])]
    public string $title = '';

    #[Groups(['admin_statistics_report:read'])]
    public string $description = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $chart = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $charts = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $stats = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $statsGroups = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $table = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $filters = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['admin_statistics_report:read'])]
    public array $meta = [];

    public function getId(): string
    {
        return $this->id;
    }
}
