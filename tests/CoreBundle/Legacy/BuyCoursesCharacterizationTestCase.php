<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use PDO;
use Plugin;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

use const PHP_BINARY;

/**
 * Shared harness of the BuyCourses characterization tests (public/plugin/BuyCourses).
 *
 * The plugin tables (plugin_buycourses_*) are not created by doctrine:schema:create: the plugin installer
 * creates them. DDL inside a test would break the DAMA savepoint, so setUpBeforeClass() runs the plugin's
 * own install() ONCE, before DAMA opens its transaction, and only when a table or column is missing (idempotent,
 * so it works on a fresh CI database and on one that already has the tables). The tables are then left in place.
 *
 * Fixtures use the fixed users (admin id 1, anon id 2) and courses 1 and 2 and are rolled back by DAMA.
 */
abstract class BuyCoursesCharacterizationTestCase extends KernelTestCase
{
    protected Connection $conn;
    protected BuyCoursesPlugin $plugin;

    private static bool $schemaChecked = false;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        // A plain PDO connection: booting the kernel here would make DAMA open its transaction too early.
        $dsn = \sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $_SERVER['DATABASE_HOST'] ?? '127.0.0.1',
            $_SERVER['DATABASE_PORT'] ?? '3306',
            $_SERVER['DATABASE_NAME'] ?? 'chamilo'
        );
        $pdo = new PDO($dsn, $_SERVER['DATABASE_USER'] ?? 'root', $_SERVER['DATABASE_PASSWORD'] ?? '');
        $count = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE 'plugin\\_buycourses\\_%'"
        )->fetchColumn();
        $hasVatColumns = $count > 0
            && (bool) $pdo->query("SHOW COLUMNS FROM plugin_buycourses_global_config LIKE 'seller_vat_number'")->fetch();
        // Installs made before the subscription prices got their 2 decimals are repaired by update().
        $priceColumn = $count > 0
            ? $pdo->query("SHOW COLUMNS FROM plugin_buycourses_subscription LIKE 'price'")->fetch(PDO::FETCH_ASSOC)
            : false;
        $hasCents = false !== $priceColumn && str_contains((string) $priceColumn['Type'], ',2)');
        // The installer creates 30 plugin tables.
        if ($count >= 30 && $hasVatColumns && $hasCents) {
            return;
        }

        $script = __DIR__.'/BuyCoursesInstallSchema.php';
        exec('APP_ENV=test '.escapeshellarg(PHP_BINARY).' -d memory_limit=2G '.escapeshellarg($script).' 2>&1', $out, $status);
        if (0 !== $status) {
            throw new RuntimeException('BuyCourses schema install failed: '.implode("\n", $out));
        }
    }

    protected static function prepareLegacyContext(): void
    {
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create('/plugin/BuyCourses/src/index.php');
        $request->setSession($session);
        // The plugin language lookup resolves the request locale in the language table (isocode en_US).
        $request->setLocale('en_US');
        Container::setRequest($request);

        require_once api_get_path(SYS_PLUGIN_PATH).'BuyCourses/src/buy_course_plugin.class.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        self::prepareLegacyContext();
        $this->conn = self::getContainer()->get('doctrine')->getConnection();
        $this->plugin = new BuyCoursesPlugin();
        $this->loginAs(0);
    }

    protected function tearDown(): void
    {
        $this->loginAs(0);
        parent::tearDown();
    }

    /**
     * Sets the legacy "current user" (0 = nobody). Does not give a security token.
     */
    protected function loginAs(int $userId): void
    {
        ChamiloSession::write('_user', $userId > 0 ? ['id' => $userId] : null);
    }

    /**
     * Overrides the plugin settings read through $plugin->get() (values are strings: 'true'/'false').
     */
    protected function setPluginSettings(array $settings): void
    {
        $prop = new ReflectionProperty(Plugin::class, 'settings');
        $prop->setAccessible(true);
        $prop->setValue($this->plugin, $settings);
    }

    /**
     * Selects the currency row of a given ISO code (the lowest id) as the platform currency.
     */
    protected function selectCurrency(string $iso): int
    {
        $id = (int) $this->conn->fetchOne('SELECT MIN(id) FROM plugin_buycourses_currency WHERE iso_code = ?', [$iso]);
        $this->plugin->saveCurrency($id);

        return $id;
    }

    protected function setGlobalConfig(int $taxPerc, int $appliesTo, string $taxName = 'VAT'): void
    {
        $this->plugin->getGlobalParameters();
        $this->conn->executeStatement(
            'UPDATE plugin_buycourses_global_config SET global_tax_perc = ?, tax_applies_to = ?, tax_name = ? WHERE id = 1',
            [$taxPerc, $appliesTo, $taxName]
        );
    }

    protected function makeItem(int $productId, float $price, ?int $taxPerc = null, int $type = 1, ?int $currencyId = null): int
    {
        $currencyId ??= (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1');
        $this->conn->insert('plugin_buycourses_item', [
            'currency_id' => $currencyId,
            'product_type' => $type,
            'product_id' => $productId,
            'price' => $price,
            'tax_perc' => $taxPerc,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    protected function makeCoupon(string $code, int $type, int $amount, string $start = '2020-01-01 00:00:00', string $end = '2099-12-31 23:59:59', int $active = 1, int $timesApplied = 0): int
    {
        $this->conn->insert('plugin_buycourses_coupon', [
            'code' => $code,
            'discount_type' => $type,
            'discount_amount' => $amount,
            'valid_start' => $start,
            'valid_end' => $end,
            'delivered' => 0,
            'times_applied' => $timesApplied,
            'active' => $active,
        ]);

        return (int) $this->conn->lastInsertId();
    }

    protected function makeSale(array $override = []): int
    {
        $currencyId = (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1');
        $this->conn->insert('plugin_buycourses_sale', array_merge([
            'currency_id' => $currencyId,
            'reference' => 'bc_ref_'.uniqid('', true),
            'date' => '2026-01-15 10:00:00',
            'user_id' => 1,
            'product_type' => 1,
            'product_name' => 'bc_product',
            'product_id' => 1,
            'price' => '100.00',
            'status' => 0,
            'payment_type' => 2,
            'invoice' => 0,
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    protected function saleRow(int $id): array
    {
        return $this->conn->fetchAssociative('SELECT * FROM plugin_buycourses_sale WHERE id = ?', [$id]) ?: [];
    }
}
