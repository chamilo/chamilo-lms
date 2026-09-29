<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\AdminStatistics;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\AdminStatistics\AdminStatisticsActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminStatisticsAction',
    operations: [
        new Post(
            uriTemplate: '/admin/statistics/action',
            openapi: new Operation(summary: 'Run an administration statistics maintenance action'),
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'post_admin_statistics_action',
            processor: AdminStatisticsActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['admin_statistics_action:read']],
    denormalizationContext: ['groups' => ['admin_statistics_action:write']],
)]
final class AdminStatisticsAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['admin_statistics_action:read'])]
    public string $id = 'admin_statistics_action';

    #[Groups(['admin_statistics_action:write'])]
    public string $report = '';

    #[Groups(['admin_statistics_action:write'])]
    public string $action = '';

    /**
     * @var int[]
     */
    #[Groups(['admin_statistics_action:write'])]
    public array $ids = [];

    #[Groups(['admin_statistics_action:write'])]
    public string $ceiling = '';

    #[Groups(['admin_statistics_action:write'])]
    public bool $activeOnly = false;

    #[Groups(['admin_statistics_action:write'])]
    public ?int $userId = null;

    #[Groups(['admin_statistics_action:write'])]
    public ?int $targetUserId = null;

    #[Groups(['admin_statistics_action:write'])]
    public string $dupMode = 'name';

    #[Groups(['admin_statistics_action:write'])]
    public int $extraFieldId = 0;

    #[Groups(['admin_statistics_action:write'])]
    public string $csrfToken = '';

    #[Groups(['admin_statistics_action:read'])]
    public bool $success = false;

    #[Groups(['admin_statistics_action:read'])]
    public string $message = '';

    #[Groups(['admin_statistics_action:read'])]
    public int $affectedCount = 0;
}
