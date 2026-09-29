<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\AdminStatistics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\AdminStatistics\AdminStatisticsReport;
use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsQueryService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<AdminStatisticsReport>
 */
final readonly class AdminStatisticsReportProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private AdminStatisticsQueryService $queryService,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminStatisticsReport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $reportName = trim((string) $request->query->get('report', 'courses'));
        if ('' === $reportName) {
            $reportName = 'courses';
        }

        $data = $this->queryService->getReport($reportName, $request->query->all());

        $report = new AdminStatisticsReport();
        $report->id = 'admin_statistics_'.$reportName;
        $report->report = $reportName;
        $report->title = (string) ($data['title'] ?? '');
        $report->description = (string) ($data['description'] ?? '');
        $report->chart = \is_array($data['chart'] ?? null) ? $data['chart'] : [];
        $report->charts = \is_array($data['charts'] ?? null) ? array_values($data['charts']) : [];
        $report->stats = \is_array($data['stats'] ?? null) ? array_values($data['stats']) : [];
        $report->statsGroups = \is_array($data['statsGroups'] ?? null) ? array_values($data['statsGroups']) : [];
        $report->table = \is_array($data['table'] ?? null) ? $data['table'] : [];
        $report->filters = \is_array($data['filters'] ?? null) ? $data['filters'] : [];
        $report->meta = \is_array($data['meta'] ?? null) ? $data['meta'] : [];
        if (\in_array($reportName, ['zombies', 'duplicated_users'], true)) {
            $report->meta['csrfToken'] = $this->csrfTokenManager->getToken('admin_statistics_action')->getValue();
        }

        return $report;
    }
}
