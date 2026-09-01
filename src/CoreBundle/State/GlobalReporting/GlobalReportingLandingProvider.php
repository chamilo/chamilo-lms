<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\GlobalReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\GlobalReporting\GlobalReportingDashboard;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContextResolver;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingQueryService;

use const PHP_SESSION_ACTIVE;

/**
 * Cheap counterpart to GlobalReportingDashboardProvider: resolves only the /reporting
 * redirect target, without running the expensive followed-user/generic-metrics queries.
 * Used by the router to decide where to land before the full dashboard has loaded.
 *
 * @implements ProviderInterface<GlobalReportingDashboard>
 */
final readonly class GlobalReportingLandingProvider implements ProviderInterface
{
    public function __construct(
        private GlobalReportingContextResolver $contextResolver,
        private GlobalReportingQueryService $queryService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): GlobalReportingDashboard {
        $reportingContext = $this->contextResolver->resolve();

        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $resource = new GlobalReportingDashboard();
        $resource->redirectUrl = $this->queryService->resolveLandingUrl($reportingContext);

        return $resource;
    }
}
