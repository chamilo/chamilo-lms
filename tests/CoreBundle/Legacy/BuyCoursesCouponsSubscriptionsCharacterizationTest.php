<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;

/**
 * Characterization of the coupon and subscription (time-limited course/session access) code of BuyCoursesPlugin
 * (fixtures prefix bc_).
 */
final class BuyCoursesCouponsSubscriptionsCharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->selectCurrency('EUR');
        $this->setGlobalConfig(0, 0);
        $this->setPluginSettings(['tax_enable' => 'false']);
        $this->loginAs(2);
    }

    private function coupon(array $coupon = []): array
    {
        return array_merge([
            'code' => 'BC_C1',
            'discount_type' => 1,
            'discount_amount' => 10,
            'valid_start' => '2026-01-01 00:00:00',
            'valid_end' => '2099-12-31 23:59:59',
            'times_applied' => 0,
            'active' => 1,
        ], $coupon);
    }

    // ------------------------------------------------------------------ coupons

    public function testAddNewCouponStoresItemsAndDefaults(): void
    {
        $this->assertTrue($this->plugin->addNewCoupon($this->coupon(['courses' => [1, 2], 'sessions' => []])));

        $row = $this->plugin->getCouponByCode('BC_C1');
        $this->assertSame(0, (int) $row['delivered']);
        $this->assertSame(10, (int) $row['discount_amount']);
        $info = $this->plugin->getCouponInfo((int) $row['id']);
        $this->assertSame([1, 2], array_map('intval', array_column($info['courses'], 'id')));
        $this->assertSame(['AI Act', 'Using Chamilo'], array_column($info['courses'], 'title'));
        $this->assertSame([], $info['sessions']);
        $this->assertSame([], $info['services']);
    }

    public function testAddNewCouponRefusesADuplicateCode(): void
    {
        $this->assertTrue($this->plugin->addNewCoupon($this->coupon()));
        $this->assertFalse($this->plugin->addNewCoupon($this->coupon(['discount_amount' => 99])));

        $this->assertSame(1, (int) $this->conn->fetchOne("SELECT COUNT(*) FROM plugin_buycourses_coupon WHERE code = 'BC_C1'"));
        $this->assertSame(10, (int) $this->plugin->getCouponByCode('BC_C1')['discount_amount']);
    }

    public function testCouponAmountKeepsItsCents(): void
    {
        $this->plugin->addNewCoupon($this->coupon(['code' => 'BC_DEC', 'discount_type' => 2, 'discount_amount' => '7.50']));

        // Fixed: coupon.discount_amount was an INT column, so a fractional amount-off coupon was rounded by MySQL
        // (7.50 -> 8): the shop took 0.50 more off than the manager typed. The column is DECIMAL(10,2) now
        // (update() converts existing installs).
        $this->assertEquals(7.5, $this->plugin->getCouponByCode('BC_DEC')['discount_amount']);
    }

    public function testCouponAmountColumnIsDecimalWithTwoDecimalsInTheInstalledSchemaAndTheInstaller(): void
    {
        // On a database where the table already exists with the right type the test above cannot tell whether the
        // installer still creates it that way, so pin both: the installed column and the installer's own definition.
        $type = (string) $this->conn->fetchOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plugin_buycourses_coupon' AND COLUMN_NAME = 'discount_amount'"
        );
        $this->assertSame('decimal(10,2)', strtolower($type));

        $installer = (string) file_get_contents(__DIR__.'/../../../public/plugin/BuyCourses/database.php');
        $this->assertMatchesRegularExpression(
            "/couponTable->addColumn\\('discount_amount', Types::DECIMAL, \\['precision' => 10, 'scale' => 2\\]\\)/",
            $installer
        );
    }

    public function testSubscriptionAmountColumnsKeepTwoDecimalsInTheInstalledSchemaAndTheInstaller(): void
    {
        // The subscription amounts were created as DECIMAL(10,0), which rounded every price to a whole unit.
        $columns = [
            'plugin_buycourses_subscription' => ['price'],
            'plugin_buycourses_subscription_rel_sale' => ['price', 'price_without_tax', 'tax_amount', 'price_without_discount', 'discount_amount'],
        ];
        foreach ($columns as $table => $fields) {
            foreach ($fields as $field) {
                $type = (string) $this->conn->fetchOne(
                    'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $field]
                );
                $this->assertSame('decimal(10,2)', strtolower($type), $table.'.'.$field);
            }
        }

        $installer = (string) file_get_contents(__DIR__.'/../../../public/plugin/BuyCourses/database.php');
        foreach (['subscriptionTable' => ['price'], 'subscriptionSaleTable' => ['price', 'price_without_tax', 'tax_amount', 'price_without_discount', 'discount_amount']] as $var => $fields) {
            foreach ($fields as $field) {
                $this->assertMatchesRegularExpression(
                    '/\$'.$var.'->addColumn\(\''.$field.'\', Types::DECIMAL, \[\'precision\' => 10, \'scale\' => 2/',
                    $installer,
                    $var.'.'.$field
                );
            }
        }
    }

    public function testCouponLookupByIdAndCodeRespectsTheValidityWindowAndProduct(): void
    {
        $id = $this->makeCoupon('BC_WIN', 1, 20, '2026-06-01 00:00:00', '2026-06-30 23:59:59');
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $id, 'product_type' => 1, 'product_id' => 1]);

        // without a product the window is not checked
        $this->assertSame($id, (int) $this->plugin->getCouponByCode('BC_WIN')['id']);
        $this->assertSame($id, (int) $this->plugin->getCoupon($id, 0, 0)['id']);
        // the window is "now": 2026-06 is in the past of the test calendar (2026-11), so with a product it is expired
        $this->assertEmpty($this->plugin->getCouponByCode('BC_WIN', 1, 1));
        $this->assertEmpty($this->plugin->getCoupon($id, 1, 1));

        $open = $this->makeCoupon('BC_OPEN', 1, 20);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $open, 'product_type' => 1, 'product_id' => 1]);
        $this->assertSame($open, (int) $this->plugin->getCouponByCode('BC_OPEN', 1, 1)['id']);
        $this->assertEmpty($this->plugin->getCouponByCode('BC_OPEN', 2, 1), 'other product type');
        $this->assertEmpty($this->plugin->getCouponByCode('BC_OPEN', 1, 2), 'other product id');
        $this->assertEmpty($this->plugin->getCouponByCode('BC_UNKNOWN'));
    }

    public function testDisabledCouponIsNotRedeemableByCodeOrId(): void
    {
        $off = $this->makeCoupon('BC_DISABLED', 1, 20, active: 0);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $off, 'product_type' => 1, 'product_id' => 1]);

        $this->assertEmpty($this->plugin->getCouponByCode('BC_DISABLED', 1, 1));
        $this->assertEmpty($this->plugin->getCoupon($off, 1, 1));
        // without a product it is the plain lookup used by the coupon admin screens, which must still find it
        $this->assertSame($off, (int) $this->plugin->getCouponByCode('BC_DISABLED')['id']);
    }

    public function testDisabledServiceCouponIsNotRedeemable(): void
    {
        $this->conn->insert('plugin_buycourses_services', $this->serviceRow('bc_service'));
        $serviceId = (int) $this->conn->lastInsertId();
        $off = $this->makeCoupon('BC_SRVOFF', 2, 5, active: 0);
        $this->conn->insert('plugin_buycourses_coupon_rel_service', ['coupon_id' => $off, 'service_id' => $serviceId]);

        $this->assertEmpty($this->plugin->getCouponServiceByCode('BC_SRVOFF', $serviceId));
        $this->assertEmpty($this->plugin->getCouponService($off, $serviceId));
    }

    public function testCouponCodeLookupIsCaseInsensitive(): void
    {
        $this->makeCoupon('BC_Case', 1, 5);
        // INTENTIONAL: MySQL's default collation makes coupon codes case-insensitive, which suits buyers who type the
        // code by hand (and makes the duplicate-code check case-insensitive too).
        $this->assertNotEmpty($this->plugin->getCouponByCode('bc_case'));
    }

    public function testCouponCodeWithQuoteDoesNotBreakTheQuery(): void
    {
        $this->assertEmpty($this->plugin->getCouponByCode("x' OR '1'='1"));
        $this->assertEmpty($this->plugin->getCouponByCode("x' OR '1'='1", 1, 1));
    }

    public function testListCouponsByStatus(): void
    {
        $on = $this->makeCoupon('BC_ON', 1, 5, active: 1);
        $off = $this->makeCoupon('BC_OFF2', 1, 5, active: 0);
        $off2 = $this->makeCoupon('BC_OFF3', 1, 5, active: 0);

        $active = array_map('intval', array_column($this->plugin->getCouponsListByStatus(1), 'id'));
        $disabled = array_map('intval', array_column($this->plugin->getCouponsListByStatus(0), 'id'));
        $this->assertContains($on, $active);
        $this->assertNotContains($off, $active);
        $this->assertContains($off, $disabled);
        $this->assertNotContains($on, $disabled, 'status 0 lists the disabled coupons only');
        $this->assertLessThan(array_search($off, $disabled), array_search($off2, $disabled), 'newest first');
        $this->assertGreaterThan(0, \count($this->plugin->getCouponsListByStatus(1)));
    }

    public function testUpdateCouponDataReplacesItemsButNotTheCode(): void
    {
        $this->plugin->addNewCoupon($this->coupon(['courses' => [1]]));
        $id = (int) $this->plugin->getCouponByCode('BC_C1')['id'];

        $this->plugin->updateCouponData([
            'id' => $id,
            'code' => 'BC_C1',
            'valid_start' => '2026-02-01 00:00:00',
            'valid_end' => '2030-01-01 00:00:00',
            'times_applied' => 3,
            'active' => 0,
            'courses' => [2],
        ]);

        $row = $this->plugin->getCouponByCode('BC_C1');
        $this->assertSame('2026-02-01 00:00:00', $row['valid_start']);
        $this->assertSame(3, (int) $row['times_applied']);
        $this->assertSame(0, (int) $row['active']);
        $this->assertSame(10, (int) $row['discount_amount'], 'amount and type are not editable');
        $this->assertSame([2], array_map('intval', array_column($this->plugin->getCouponInfo($id)['courses'], 'id')));
    }

    public function testUpdateCouponDelivered(): void
    {
        $id = $this->makeCoupon('BC_DELIV', 1, 5);
        $this->plugin->updateCouponDelivered($id);
        $this->plugin->updateCouponDelivered($id);
        $this->assertSame(2, (int) $this->conn->fetchOne('SELECT delivered FROM plugin_buycourses_coupon WHERE id = ?', [$id]));
    }

    public function testCouponUsageLimitIsEnforcedOnRedemption(): void
    {
        $id = $this->makeCoupon('BC_LIM', 1, 5, timesApplied: 1);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $id, 'product_type' => 1, 'product_id' => 1]);

        // Fixed: times_applied (the cap shown in the coupon list, 0 = unlimited) was compared with "delivered"
        // nowhere, so a coupon delivered twice with a cap of 1 still redeemed.
        $this->assertNotEmpty($this->plugin->getCouponByCode('BC_LIM', 1, 1), 'nothing delivered yet');
        $this->plugin->updateCouponDelivered($id);
        $this->assertEmpty($this->plugin->getCouponByCode('BC_LIM', 1, 1), 'cap of 1 reached');
        $this->assertEmpty($this->plugin->getCoupon($id, 1, 1, true), 'the checks before a sale refuse it too');
        // The confirmation pages look the coupon up after its own sale was delivered: they must still find it.
        $this->assertNotEmpty($this->plugin->getCoupon($id, 1, 1));
        // The admin lookup without a product is not a redemption.
        $this->assertNotEmpty($this->plugin->getCouponByCode('BC_LIM'));

        $unlimited = $this->makeCoupon('BC_UNL', 1, 5, timesApplied: 0);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $unlimited, 'product_type' => 1, 'product_id' => 1]);
        $this->plugin->updateCouponDelivered($unlimited);
        $this->plugin->updateCouponDelivered($unlimited);
        $this->assertNotEmpty($this->plugin->getCouponByCode('BC_UNL', 1, 1), '0 means unlimited');
    }

    public function testServiceCouponUsageLimitIsEnforcedOnRedemption(): void
    {
        $this->conn->insert('plugin_buycourses_services', $this->serviceRow('bc_service'));
        $serviceId = (int) $this->conn->lastInsertId();
        $id = $this->makeCoupon('BC_SRVLIM', 2, 5, timesApplied: 1);
        $this->conn->insert('plugin_buycourses_coupon_rel_service', ['coupon_id' => $id, 'service_id' => $serviceId]);

        $this->assertNotEmpty($this->plugin->getCouponServiceByCode('BC_SRVLIM', $serviceId));
        $this->plugin->updateCouponDelivered($id);
        $this->assertEmpty($this->plugin->getCouponServiceByCode('BC_SRVLIM', $serviceId));
        $this->assertEmpty($this->plugin->getCouponService($id, $serviceId, true));
        $this->assertNotEmpty($this->plugin->getCouponService($id, $serviceId));
    }

    public function testPluginIsEnabledOnlyWhenAPaymentMethodIsReallyOn(): void
    {
        // Plugin settings are strings: 'false' is truthy, so isEnabled() used to be true with every method off.
        $methods = ['paypal_enable', 'transfer_enable', 'culqi_enable', 'stripe_enable', 'cecabank_enable', 'btcpay_enable'];
        $off = array_fill_keys($methods, 'false');

        $this->setPluginSettings($off);
        $this->assertFalse($this->plugin->isEnabled(), "every method 'false'");
        $this->setPluginSettings(array_fill_keys($methods, ''));
        $this->assertFalse($this->plugin->isEnabled(), 'every method empty');
        $this->setPluginSettings([]);
        $this->assertFalse($this->plugin->isEnabled(), 'unset');

        foreach ($methods as $method) {
            $this->setPluginSettings([$method => 'true'] + $off);
            $this->assertTrue($this->plugin->isEnabled(), $method.' on');
        }
        $this->setPluginSettings(['paypal_enable' => true]);
        $this->assertTrue($this->plugin->isEnabled(), 'a real boolean true');
    }

    public function testServiceCouponLookup(): void
    {
        $id = $this->makeCoupon('BC_SRV', 2, 5);
        $this->conn->insert('plugin_buycourses_services', $this->serviceRow('bc_service'));
        $serviceId = (int) $this->conn->lastInsertId();
        $this->conn->insert('plugin_buycourses_coupon_rel_service', ['coupon_id' => $id, 'service_id' => $serviceId]);

        $this->assertSame($id, (int) $this->plugin->getCouponServiceByCode('BC_SRV', $serviceId)['id']);
        $this->assertSame($id, (int) $this->plugin->getCouponService($id, $serviceId)['id']);
        $this->assertEmpty($this->plugin->getCouponServiceByCode('BC_SRV', $serviceId + 1));
        $this->assertEmpty($this->plugin->getCouponServiceByCode('BC_NOPE', $serviceId));
        $this->assertSame([$serviceId], array_map('intval', array_column($this->plugin->getCouponInfo($id)['services'], 'id')));
    }

    // ------------------------------------------------------------- frequencies

    public function testFrequenciesLifecycle(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_subscription_period');
        $this->plugin->addFrequency(30, 'bc_monthly');
        $this->plugin->addFrequency(365, 'bc_yearly');

        $this->assertSame([30 => 'bc_monthly', 365 => 'bc_yearly'], $this->plugin->getFrequencies());
        $this->assertSame('bc_yearly', $this->plugin->selectFrequency(365)['name']);

        $this->assertSame(1, $this->plugin->updateFrequency(30, 'bc_month'));
        $this->assertSame('bc_month', $this->plugin->selectFrequency(30)['name']);
        $this->assertSame(1, $this->plugin->deleteFrequency(365));
        $this->assertSame([30 => 'bc_month'], $this->plugin->getFrequencies());
    }

    // ------------------------------------------------------------ subscriptions

    private function subscription(array $frequencies, array $override = []): array
    {
        return array_merge([
            'product_type' => 1,
            'product_id' => 1,
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'tax_perc' => null,
            'frequencies' => $frequencies,
        ], $override);
    }

    public function testAddNewSubscriptionRegistersEveryFrequency(): void
    {
        $this->assertTrue($this->plugin->addNewSubscription($this->subscription([
            ['duration' => 30, 'price' => 10],
            ['duration' => 365, 'price' => 100],
        ])));

        $rows = $this->plugin->getSubscriptions(1, 1);
        $this->assertSame([30, 365], array_map('intval', array_column(array_values($rows), 'duration')), 'ordered by duration ASC');
        $this->assertSame([], $this->plugin->getSubscriptions(1, 2));
        $this->assertSame([], $this->plugin->getSubscriptions(2, 1));
    }

    public function testAddNewSubscriptionRejectsBadFrequencies(): void
    {
        $this->assertFalse($this->plugin->addNewSubscription($this->subscription([])));
        $this->assertFalse($this->plugin->addNewSubscription($this->subscription([['duration' => 0, 'price' => 5]])));
        $this->assertFalse($this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 0]])));
        $this->assertSame([], $this->plugin->getSubscriptions(1, 1));
    }

    public function testAddNewSubscriptionRefusesAnExistingDuration(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 10]]));
        $this->assertFalse($this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 99]])));
        $this->assertSame(10.0, (float) $this->plugin->getSubscription(1, 1, 30)['price']);
    }

    public function testSubscriptionPriceKeepsItsCents(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 19.99]]));

        $this->assertEqualsWithDelta(19.99, (float) $this->conn->fetchOne('SELECT price FROM plugin_buycourses_subscription WHERE duration = 30 AND product_id = 1'), 0.0001);
    }

    public function testGetSubscriptionAppliesCouponAndTax(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 50]], ['tax_perc' => 20]));
        $this->setGlobalConfig(0, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);

        $sub = $this->plugin->getSubscription(1, 1, 30, ['discount_type' => 2, 'discount_amount' => 10]);

        $this->assertSame(40.0, $sub['price']);
        $this->assertSame(8.0, $sub['tax_amount']);
        $this->assertSame(48.0, $sub['total_price']);
        $this->assertSame('EUR', $sub['iso_code']);
        $this->assertSame([], $this->plugin->getSubscription(1, 1, 31));
    }

    public function testUpdateSubscriptionTaxAndDelete(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 10], ['duration' => 60, 'price' => 20]]));

        $this->assertTrue($this->plugin->updateSubscription(1, 1, 7));
        $this->assertSame(7, (int) $this->plugin->getSubscription(1, 1, 60)['tax_perc']);
        $this->assertSame(1, $this->plugin->deleteSubscription(1, 1, 30));
        $this->assertSame([60], array_map('intval', array_column(array_values($this->plugin->getSubscriptions(1, 1)), 'duration')));
    }

    public function testSubscriptionItemLookups(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 10], ['duration' => 60, 'price' => 20]]));

        $this->assertSame(30, (int) $this->plugin->getSubscriptionItem(1, 1)['duration']);
        $this->assertSame('EUR', $this->plugin->getSubscriptionItemByProduct(1, 1)['iso_code']);
        $this->assertCount(2, $this->plugin->getSubscriptionsItemsByProduct(1, 1));
        $this->assertNull($this->plugin->getSubscriptionsItemsByProduct(1, 2));
        $this->assertCount(1, $this->plugin->getSubscriptionsItemsByDuration(60));
    }

    public function testRegisterSubscriptionSaleComputesEndFromDurationAndAppliesCoupon(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 80]]));
        $coupon = $this->makeCoupon('BC_SUB', 1, 25);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $coupon, 'product_type' => 1, 'product_id' => 1]);

        $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30, $coupon);
        $sale = $this->plugin->getSubscriptionSale($saleId);

        $this->assertSame(2, (int) $sale['user_id']);
        $this->assertSame('AI Act', $sale['product_name']);
        $this->assertSame(0, (int) $sale['status']);
        $this->assertSame(0, (int) $sale['expired']);
        $this->assertEquals(60, (float) $sale['price']);
        $this->assertEquals(20, (float) $sale['discount_amount']);
        $this->assertEquals(80, (float) $sale['price_without_discount']);
        $days = (strtotime($sale['subscription_end']) - time()) / 86400;
        $this->assertEqualsWithDelta(30, $days, 0.01);
    }

    public function testRegisterSubscriptionSaleCapsTheDiscountAtThePrice(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 80]]));
        $coupon = $this->makeCoupon('BC_SUBBIG', 2, 500);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $coupon, 'product_type' => 1, 'product_id' => 1]);

        $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30, $coupon);
        $sale = $this->plugin->getSubscriptionSale($saleId);

        $this->assertEquals(0, (float) $sale['price']);
        $this->assertEquals(80, (float) $sale['discount_amount']);
    }

    public function testSubscriptionSaleKeepsTheCentsOfPriceAndTax(): void
    {
        $this->setGlobalConfig(0, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 19.99]], ['tax_perc' => 21]));

        $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30);
        $sale = $this->conn->fetchAssociative('SELECT * FROM plugin_buycourses_subscription_rel_sale WHERE id = ?', [$saleId]);

        // 19.99 * 21% = 4.1979 -> 4.20 ; total 24.19
        $this->assertSame('24.19', $sale['price']);
        $this->assertSame('19.99', $sale['price_without_tax']);
        $this->assertSame('4.20', $sale['tax_amount']);
        $this->assertSame('0.00', $sale['discount_amount']);
    }

    /**
     * @dataProvider serverTimezones
     */
    public function testSubscriptionEndIsStoredInUtcWhateverTheServerTimezone(string $timezone): void
    {
        // The sale date is UTC (api_get_utc_datetime); the end of the subscription must be on the same clock, i.e.
        // exactly 30 days later. With date() it carried the PHP timezone offset (e.g. +14h) on top.
        $previous = date_default_timezone_get();
        date_default_timezone_set($timezone);

        try {
            $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 10]]));
            $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30);
            $sale = $this->conn->fetchAssociative('SELECT * FROM plugin_buycourses_subscription_rel_sale WHERE id = ?', [$saleId]);
        } finally {
            date_default_timezone_set($previous);
        }

        $this->assertNotEmpty($sale);
        $seconds = strtotime($sale['subscription_end'].' UTC') - strtotime($sale['date'].' UTC');
        $this->assertEqualsWithDelta(30 * 86400, $seconds, 5, 'subscription_end = sale date + 30 days, both in UTC ('.$timezone.')');
    }

    public static function serverTimezones(): iterable
    {
        yield 'UTC+14' => ['Pacific/Kiritimati'];
        yield 'UTC-11' => ['Pacific/Pago_Pago'];
        yield 'UTC' => ['UTC'];
    }

    public function testRegisterSubscriptionSaleRejectsOtherInputs(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 80]]));

        $this->assertFalse($this->plugin->registerSubscriptionSale(1, 1, 99, 30));
        $this->assertFalse($this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY, 30), 'btcpay is not a subscription gateway');
        $this->assertFalse($this->plugin->registerSubscriptionSale(2, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30), 'no subscription for course 2');
    }

    public function testCompleteSubscriptionSaleSubscribesTheBuyer(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 80]]));
        $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30);
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = 2 AND c_id = 1');

        $this->assertTrue($this->plugin->completeSubscriptionSale($saleId));

        // the buyer is subscribed to the course and the sale is completed (as completeSale does)
        $this->assertSame($before + 1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = 2 AND c_id = 1'));
        $this->assertSame(1, (int) $this->plugin->getSubscriptionSale($saleId)['status']);
        $this->assertTrue($this->plugin->completeSubscriptionSale($saleId), 'idempotent');
    }

    public function testCompleteSubscriptionSaleOfAnUnknownCourseStaysPending(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 80]]));
        $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30);
        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_rel_sale SET product_id = 987654 WHERE id = ?', [$saleId]);

        $this->assertFalse($this->plugin->completeSubscriptionSale($saleId));
        $this->assertSame(0, (int) $this->plugin->getSubscriptionSale($saleId)['status']);
    }

    public function testCancelSubscriptionSale(): void
    {
        $this->plugin->addNewSubscription($this->subscription([['duration' => 30, 'price' => 80]]));
        $saleId = (int) $this->plugin->registerSubscriptionSale(1, 1, BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 30);
        $this->plugin->cancelSubscriptionSale($saleId);
        $this->assertSame(-1, (int) $this->plugin->getSubscriptionSale($saleId)['status']);

        // Only a pending sale is cancelled: a completed one keeps its status (same rule as cancelSale()).
        $this->conn->executeStatement('UPDATE plugin_buycourses_subscription_rel_sale SET status = 1 WHERE id = ?', [$saleId]);
        $this->plugin->cancelSubscriptionSale($saleId);
        $this->assertSame(1, (int) $this->plugin->getSubscriptionSale($saleId)['status']);
    }

    public function testSubscriptionSaleListByUserIdOnlyCompleted(): void
    {
        $base = [
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'date' => '2026-01-15 10:00:00', 'user_id' => 2, 'product_type' => 1, 'product_name' => 'bc_p', 'product_id' => 1,
            'price' => 10, 'payment_type' => 2, 'invoice' => 0, 'price_without_discount' => 0, 'discount_amount' => 0,
            'subscription_end' => '2026-02-14 10:00:00', 'expired' => 0,
        ];
        $this->conn->insert('plugin_buycourses_subscription_rel_sale', $base + ['reference' => 'bc_s1', 'status' => 1]);
        $this->conn->insert('plugin_buycourses_subscription_rel_sale', $base + ['reference' => 'bc_s2', 'status' => 0]);
        $this->conn->insert('plugin_buycourses_subscription_rel_sale', array_merge($base, ['user_id' => 1]) + ['reference' => 'bc_s3', 'status' => 1]);

        $refs = array_column($this->plugin->getSubscriptionSaleListByUserId(2), 'reference');
        $this->assertSame(['bc_s1'], array_values(array_filter($refs, static fn ($r) => str_starts_with($r, 'bc_s'))));
    }

    // ------------------------------------------------------------------ helpers

    private function serviceRow(string $name, array $override = []): array
    {
        return array_merge([
            'name' => $name,
            'description' => 'bc_desc',
            'price' => '100.00',
            'duration_days' => 30,
            'applies_to' => 1,
            'owner_id' => 1,
            'active' => 1,
            'visibility' => 1,
            'video_url' => '',
            'image' => '',
            'service_information' => '',
        ], $override);
    }
}
