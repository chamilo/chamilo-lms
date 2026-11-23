<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Doctrine\DBAL\Connection;
use H5pImportPlugin;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for H5pImportPlugin::fixTotalTimeInLpItemView(): the learning path item view takes the sum
 * of the time recorded in the H5P results of that user for that view (c_lp_item_view, plugin_h5p_import_results).
 */
final class H5pImportTier3CharacterizationTest extends KernelTestCase
{
    use PluginTier3SchemaTrait;
    private const USER = 987021;
    private const OTHER = 987022;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTable('H5pImport', 'plugin_h5p_import_results');
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new HttpSession(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/H5pImport/start.php');
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);

        require_once api_get_path(SYS_PLUGIN_PATH).'H5pImport/H5pImportPlugin.php';
    }

    public function testTheViewTakesTheSumOfTheUsersResultTimesAndTheSumIsReturned(): void
    {
        $view = $this->itemView(5, 'completed');
        $this->result(self::USER, $view, 30);
        $this->result(self::USER, $view, 12);
        $this->result(self::OTHER, $view, 1000);
        $otherView = $this->itemView(9, 'completed');
        $this->result(self::USER, $otherView, 500);

        $this->assertSame(42, H5pImportPlugin::fixTotalTimeInLpItemView($view, self::USER));

        $row = $this->viewRow($view);
        $this->assertSame('42', (string) $row['total_time']);
        $this->assertSame('completed', $row['status'], 'The status is kept when there is time');
        $this->assertSame('9', (string) $this->viewRow($otherView)['total_time'], 'Another view is not touched');
    }

    public function testWithoutAnyTimeTheViewIsResetToNotAttempted(): void
    {
        $view = $this->itemView(77, 'incomplete');
        $this->result(self::OTHER, $view, 1000);
        $zero = $this->itemView(78, 'completed');
        $this->result(self::USER, $zero, 0);

        $this->assertSame(0, H5pImportPlugin::fixTotalTimeInLpItemView($view, self::USER));
        $row = $this->viewRow($view);
        $this->assertSame('0', (string) $row['total_time']);
        $this->assertSame('not attempted', $row['status']);

        $this->assertSame(0, H5pImportPlugin::fixTotalTimeInLpItemView($zero, self::USER));
        $this->assertSame('not attempted', $this->viewRow($zero)['status']);
    }

    public function testAnUnknownViewReturnsZeroAndWritesNothing(): void
    {
        $view = $this->itemView(5, 'completed');
        $this->result(self::USER, $view, 30);

        $this->assertSame(0, H5pImportPlugin::fixTotalTimeInLpItemView($view + 100000, self::USER));
        $this->assertSame('5', (string) $this->viewRow($view)['total_time']);
    }

    private function conn(): Connection
    {
        return self::getContainer()->get('doctrine')->getConnection();
    }

    private function itemView(int $totalTime, string $status): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO c_lp_item_view (lp_item_id, lp_view_id, view_count, start_time, total_time, score, status, core_exit)
             VALUES (NULL, NULL, 1, 0, ?, 0, ?, \'none\')',
            [$totalTime, $status]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function result(int $user, int $view, int $time): void
    {
        $this->conn()->executeStatement(
            "INSERT INTO plugin_h5p_import_results (c_id, session_id, plugin_h5p_import_id, user_id, c_lp_item_view_id, start_time, total_time, score, max_score, created_at, modified_at)
             VALUES (1, NULL, 1, ?, ?, 0, ?, 0, 0, '2030-01-02 03:04:05', '2030-01-02 03:04:05')",
            [$user, $view, $time]
        );
    }

    private function viewRow(int $view): array
    {
        return $this->conn()->fetchAssociative('SELECT total_time, status FROM c_lp_item_view WHERE iid = ?', [$view]);
    }
}
