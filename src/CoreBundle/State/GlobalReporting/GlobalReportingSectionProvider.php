<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\GlobalReporting;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\GlobalReporting\GlobalReportingSection;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContextResolver;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<GlobalReportingSection> */
final readonly class GlobalReportingSectionProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GlobalReportingContextResolver $contextResolver,
        private GlobalReportingSectionQueryService $queryService,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): GlobalReportingSection {
        $request = $this->requestStack->getCurrentRequest();
        $filters = null === $request ? [] : $request->query->all();
        $section = trim((string) ($filters['section'] ?? ''));
        $data = $this->queryService->getSection(
            $this->contextResolver->resolve(),
            $section,
            $filters,
        );

        $resource = new GlobalReportingSection();
        $resource->id = 'global_reporting_'.$section;
        $resource->section = $section;
        $resource->title = (string) ($data['title'] ?? '');
        $resource->total = (int) ($data['total'] ?? 0);
        $resource->page = (int) ($data['page'] ?? 1);
        $resource->itemsPerPage = (int) ($data['itemsPerPage'] ?? 20);
        $resource->summary = \is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $resource->columns = \is_array($data['columns'] ?? null) ? $data['columns'] : [];
        $resource->items = \is_array($data['items'] ?? null) ? $data['items'] : [];
        $resource->sections = \is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $resource->meta = \is_array($data['meta'] ?? null) ? $data['meta'] : [];

        return $resource;
    }
}
