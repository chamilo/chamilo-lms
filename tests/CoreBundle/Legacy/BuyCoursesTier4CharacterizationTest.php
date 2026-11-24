<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;
use ReflectionMethod;

/**
 * Tier 3 (batch 4) characterization of BuyCoursesPlugin::ensureBtcPayAccountTable(), written to pass on the
 * Database::query() implementation before it is moved to the DBAL QueryBuilder. Rolled back by DAMA.
 */
final class BuyCoursesTier4CharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    private function ensure(): void
    {
        $m = new ReflectionMethod(BuyCoursesPlugin::class, 'ensureBtcPayAccountTable');
        $m->setAccessible(true);
        $m->invoke($this->plugin);
    }

    public function testEnsureInsertsOneEmptyRowWhenTheTableIsEmpty(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_btcpay_account');

        $this->ensure();

        $rows = $this->conn->fetchAllAssociative('SELECT server_url, store_id, api_key, webhook_secret FROM plugin_buycourses_btcpay_account');
        $this->assertSame([['server_url' => '', 'store_id' => '', 'api_key' => '', 'webhook_secret' => '']], $rows);
    }

    public function testEnsureLeavesAnExistingRowUntouchedAndAddsNone(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_btcpay_account');
        $this->conn->insert('plugin_buycourses_btcpay_account', [
            'server_url' => 'https://bc.example', 'store_id' => 'bc_store', 'api_key' => 'k', 'webhook_secret' => 's',
        ]);

        $this->ensure();
        $this->ensure();

        $rows = $this->conn->fetchAllAssociative('SELECT server_url, store_id FROM plugin_buycourses_btcpay_account');
        $this->assertSame([['server_url' => 'https://bc.example', 'store_id' => 'bc_store']], $rows);
    }
}
