<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\AdminStatistics;

use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsQueryService;
use Chamilo\Tests\AbstractApiTest;

final class AdminStatisticsUserLanguageReportTest extends AbstractApiTest
{
    public function testUsersByLanguageReportReturnsChartAndCounts(): void
    {
        $service = self::getContainer()->get(AdminStatisticsQueryService::class);
        $report = $service->getReport('usersbylanguage');

        self::assertNotEmpty($report['title']);
        self::assertSame('pie', $report['chart']['type']);
        self::assertIsArray($report['stats']);
        self::assertSame(
            array_column($report['stats'], 'label'),
            $report['chart']['data']['labels']
        );
        self::assertSame(
            array_column($report['stats'], 'value'),
            $report['chart']['data']['datasets'][0]['data']
        );
    }
}
