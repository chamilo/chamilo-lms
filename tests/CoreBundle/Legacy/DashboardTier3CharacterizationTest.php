<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Framework\Container;
use Database;
use DashboardManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Characterization tests for DashboardManager::get_enabled_dashboard_blocks() (public/main/inc/lib/dashboard.lib.php).
 * Fixture paths start with "dsh3_".
 */
final class DashboardTier3CharacterizationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/main/inc/lib/dashboard.lib.php');
        $request->setSession($session);
        Container::setRequest($request);
    }

    private function conn(): Connection
    {
        return Database::getManager()->getConnection();
    }

    private function block(string $path, int $active, string $title = ''): int
    {
        $this->conn()->executeStatement(
            'INSERT INTO block (title, description, path, controller, active) VALUES (?, ?, ?, ?, ?)',
            ['' === $title ? 'title_'.$path : $title, 'desc_'.$path, $path, 'Ctl'.$path, $active]
        );

        return (int) $this->conn()->lastInsertId();
    }

    private function mine(array $blocks): array
    {
        return array_filter($blocks, static fn ($row, $path) => str_starts_with((string) $path, 'dsh3_'), ARRAY_FILTER_USE_BOTH);
    }

    public function testWithoutPathReturnsEveryActiveBlockKeyedByPath(): void
    {
        $a = $this->block('dsh3_a', 1);
        $this->block('dsh3_b', 0);
        $c = $this->block('dsh3_c', 1);

        $blocks = $this->mine(DashboardManager::get_enabled_dashboard_blocks());

        $this->assertEqualsCanonicalizing(['dsh3_a', 'dsh3_c'], array_keys($blocks));
        $this->assertSame($a, (int) $blocks['dsh3_a']['id']);
        $this->assertSame($c, (int) $blocks['dsh3_c']['id']);
        $this->assertSame('Ctldsh3_a', $blocks['dsh3_a']['controller']);
    }

    public function testRowKeepsEveryColumnUnderItsNameAndItsPosition(): void
    {
        $id = $this->block('dsh3_shape', 1);

        $row = DashboardManager::get_enabled_dashboard_blocks('dsh3_shape')['dsh3_shape'];

        $this->assertSame(
            ['id', 'user_id', 'title', 'description', 'path', 'controller', 'active', 0, 1, 2, 3, 4, 5, 6],
            array_keys($row)
        );
        $this->assertSame($id, (int) $row['id']);
        $this->assertNull($row['user_id']);
        $this->assertSame('title_dsh3_shape', $row['title']);
        $this->assertSame('desc_dsh3_shape', $row['description']);
        $this->assertSame(1, (int) $row['active']);
        $this->assertSame($row['path'], $row[4]);
        $this->assertSame($row['id'], $row[0]);
        $this->assertSame(\gettype($this->conn()->fetchOne('SELECT active FROM block WHERE id = ?', [$id])), \gettype($row['active']));
        $this->assertSame(\gettype($this->conn()->fetchOne('SELECT id FROM block WHERE id = ?', [$id])), \gettype($row['id']));
    }

    public function testPathFiltersToThatBlockOnlyAndOnlyWhenActive(): void
    {
        $this->block('dsh3_x', 1);
        $this->block('dsh3_y', 1);
        $this->block('dsh3_off', 0);

        $this->assertSame(['dsh3_x'], array_keys(DashboardManager::get_enabled_dashboard_blocks('dsh3_x')));
        $this->assertSame([], DashboardManager::get_enabled_dashboard_blocks('dsh3_off'));
        $this->assertSame([], DashboardManager::get_enabled_dashboard_blocks('dsh3_unknown'));
        // The comparison uses the column collation (case insensitive).
        $this->assertSame(['dsh3_x'], array_keys(DashboardManager::get_enabled_dashboard_blocks('DSH3_X')));
    }

    public function testPathIsBoundNotInterpolated(): void
    {
        $this->block('dsh3_inj', 1);

        $this->assertSame([], DashboardManager::get_enabled_dashboard_blocks('x" OR "1"="1'));
    }
}
