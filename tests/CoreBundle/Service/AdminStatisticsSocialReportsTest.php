<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service;

use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsQueryService;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Contacts report: like the messages reports it must not list soft-deleted accounts (a row with an empty name).
 */
final class AdminStatisticsSocialReportsTest extends KernelTestCase
{
    use ChamiloTestTrait;
    use RawReportingFixturesTrait;

    public function testContactsReportLeavesSoftDeletedUsersOut(): void
    {
        self::bootKernel();
        $live = $this->rawUser('r2f1');
        $friend = $this->rawUser('r2f2');
        $deleted = $this->rawUser('r2f3', 5, -2);
        $this->userRel($live, $friend, 3);
        $this->userRel($deleted, $friend, 3);
        $this->userRel($deleted, $live, 3);
        $this->fresh();

        $report = self::getContainer()->get(AdminStatisticsQueryService::class)->getReport('friends');

        $labels = array_column($report['stats'], 'label');
        $mine = array_values(array_filter($labels, static fn (string $l): bool => str_contains($l, 'rp_r2f')));
        $this->assertCount(1, $mine, 'only the live account is listed');
        $this->assertStringContainsString('Firstr2f1 Lastr2f1', $mine[0]);
        $this->assertNotContains('', array_map('trim', $labels), 'no row with an empty name');
        $this->assertNotContains("\n()", $labels);
    }
}
