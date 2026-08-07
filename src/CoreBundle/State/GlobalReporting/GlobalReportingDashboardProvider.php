<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\GlobalReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\GlobalReporting\GlobalReportingDashboard;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContextResolver;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingQueryService;

/** @implements ProviderInterface<GlobalReportingDashboard> */
final readonly class GlobalReportingDashboardProvider implements ProviderInterface
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
        $data = $this->queryService->getDashboard($this->contextResolver->resolve());
        $resource = new GlobalReportingDashboard();

        foreach ($data as $property => $value) {
            $resource->{$property} = $value;
        }

        return $resource;
    }
}
