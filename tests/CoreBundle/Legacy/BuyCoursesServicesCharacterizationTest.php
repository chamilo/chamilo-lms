<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

/**
 * Characterization of the service-sale flow of BuyCoursesPlugin (services bought by a user for a node: user,
 * course or session): registration, tax, coupons, repurchase blocking, status transitions, gateway helpers.
 * Fixtures prefix bc_.
 */
final class BuyCoursesServicesCharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->selectCurrency('EUR');
        $this->setGlobalConfig(0, 0);
        $this->setPluginSettings(['tax_enable' => 'false']);
        $this->loginAs(2);
    }

    private function makeService(array $override = []): int
    {
        $this->conn->insert('plugin_buycourses_services', array_merge([
            'name' => 'bc_service',
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
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    private function serviceSaleRow(int $id): array
    {
        return $this->conn->fetchAssociative('SELECT * FROM plugin_buycourses_service_sale WHERE id = ?', [$id]) ?: [];
    }

    private function makeServiceSale(int $serviceId, array $override = []): int
    {
        $this->conn->insert('plugin_buycourses_service_sale', array_merge([
            'service_id' => $serviceId,
            'reference' => 'bc_sref_'.uniqid('', true),
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'price' => '100.00',
            'node_type' => 1,
            'node_id' => 2,
            'buyer_id' => 2,
            'buy_date' => '2026-01-10 08:00:00',
            'date_start' => '2026-01-10 08:00:00',
            'date_end' => '2026-02-09 08:00:00',
            'status' => 0,
            'payment_type' => 2,
            'invoice' => 0,
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    public function testRegisterServiceSaleRejectsGatewaysThatServicesDoNotSupport(): void
    {
        $service = $this->makeService();

        foreach ([0, 4, 6, 7, 99] as $payment) {
            $this->assertFalse($this->plugin->registerServiceSale($service, $payment, 2), 'payment '.$payment);
        }
        $this->assertNotSame('', $this->plugin->getLastServiceSaleError());
    }

    public function testRegisterServiceSaleRejectsUnknownAndInactiveServices(): void
    {
        $this->assertFalse($this->plugin->registerServiceSale(999999, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2));
        $inactive = $this->makeService(['active' => 0]);
        $this->assertFalse($this->plugin->registerServiceSale($inactive, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_service_sale WHERE service_id = ?', [$inactive]));
    }

    public function testRegisterServiceSalePlain(): void
    {
        $service = $this->makeService(['duration_days' => 45]);

        $saleId = $this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2);
        $sale = $this->serviceSaleRow((int) $saleId);

        $this->assertSame(2, (int) $sale['buyer_id']);
        $this->assertSame(2, (int) $sale['node_id']);
        $this->assertSame(1, (int) $sale['node_type']);
        $this->assertSame('100.00', $sale['price']);
        $this->assertSame(0, (int) $sale['status']);
        $this->assertSame('0.00', $sale['discount_amount']);
        $this->assertSame(45 * 86400, strtotime($sale['date_end']) - strtotime($sale['buy_date']));
        $this->assertNull($sale['recurring_amount']);
        $this->assertMatchesRegularExpression('/^2-1-2-[A-Za-z0-9]{6}$/', $sale['reference']);
        $this->assertFalse($this->plugin->wasLastServiceSaleReused());
    }

    public function testRegisterServiceSaleRecordsAnAuditEntry(): void
    {
        $service = $this->makeService();
        $saleId = (int) $this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_STRIPE, 2);

        $entries = array_values($this->plugin->getAuditEntries(\BuyCoursesPlugin::AUDIT_OBJECT_SERVICE_SALE, $saleId));
        $this->assertCount(1, $entries);
        $this->assertSame(\BuyCoursesPlugin::AUDIT_ACTION_SERVICE_SALE_CREATED, $entries[0]['action']);
        $this->assertSame(\BuyCoursesPlugin::AUDIT_SOURCE_USER, $entries[0]['source']);
    }

    public function testRegisterServiceSaleTaxIsRoundedToCents(): void
    {
        $this->setGlobalConfig(10, \BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);
        $service = $this->makeService(['price' => '33.33', 'tax_perc' => 21]);

        $sale = $this->serviceSaleRow((int) $this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2));

        $this->assertSame('40.33', $sale['price']);
        $this->assertSame('33.33', $sale['price_without_tax']);
        $this->assertSame('7.00', $sale['tax_amount']);
        $this->assertSame(21, (int) $sale['tax_perc']);
    }

    public function testRegisterServiceSalePercentageCouponRoundsTheDiscount(): void
    {
        $service = $this->makeService(['price' => '99.99']);
        $coupon = $this->makeCoupon('BC_SP', 1, 15);
        $this->conn->insert('plugin_buycourses_coupon_rel_service', ['coupon_id' => $coupon, 'service_id' => $service]);

        $sale = $this->serviceSaleRow((int) $this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2, $coupon));

        // 99.99 * 15% = 14.9985 -> 15.00 ; 99.99 - 15.00 = 84.99
        $this->assertSame('15.00', $sale['discount_amount']);
        $this->assertSame('84.99', $sale['price']);
        $this->assertSame('99.99', $sale['price_without_discount']);
    }

    public function testRegisterServiceSaleCouponOfAnotherServiceIsIgnored(): void
    {
        $service = $this->makeService();
        $other = $this->makeService();
        $coupon = $this->makeCoupon('BC_SO', 2, 40);
        $this->conn->insert('plugin_buycourses_coupon_rel_service', ['coupon_id' => $coupon, 'service_id' => $other]);

        $sale = $this->serviceSaleRow((int) $this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2, $coupon));
        $this->assertSame('100.00', $sale['price']);
    }

    public function testRegisterServiceSaleCannotBeFree(): void
    {
        $service = $this->makeService(['price' => '30.00']);
        $coupon = $this->makeCoupon('BC_FREE', 2, 50);
        $this->conn->insert('plugin_buycourses_coupon_rel_service', ['coupon_id' => $coupon, 'service_id' => $service]);

        // the discount is capped at the price, which leaves nothing to charge: the purchase is refused
        $this->assertFalse($this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2, $coupon));
        $this->assertSame(0, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_service_sale WHERE service_id = ?', [$service]));
    }

    public function testRegisterServiceSaleRenewableKeepsTheRecurringAmount(): void
    {
        $service = $this->makeService(['price' => '50.00', 'renewable' => 1]);
        $sale = $this->serviceSaleRow((int) $this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_STRIPE, 2));
        $this->assertSame('50.00', $sale['recurring_amount']);
    }

    public function testSecondPendingPurchaseOfTheSameServiceAndNodeIsRefused(): void
    {
        $service = $this->makeService();
        $this->assertNotFalse($this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2));
        $this->assertFalse($this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2));
        $this->assertNotFalse($this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 1), 'another node is a different purchase');
        $this->assertSame(2, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_service_sale WHERE service_id = ?', [$service]));
    }

    public function testAnActiveCompletedPurchaseBlocksTheRepurchaseUntilItEnds(): void
    {
        $service = $this->makeService();
        $future = gmdate('Y-m-d H:i:s', time() + 86400);
        $this->makeServiceSale($service, ['status' => 1, 'date_end' => $future]);
        $this->assertFalse($this->plugin->registerServiceSale($service, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2));

        $serviceB = $this->makeService();
        $this->makeServiceSale($serviceB, ['status' => 1, 'date_end' => '2026-02-09 08:00:00']);
        $this->assertNotFalse($this->plugin->registerServiceSale($serviceB, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, 2), 'expired and not renewable');
    }

    public function testCompleteServiceSale(): void
    {
        $service = $this->makeService();
        $saleId = $this->makeServiceSale($service);

        $this->assertTrue($this->plugin->completeServiceSale($saleId, \BuyCoursesPlugin::AUDIT_SOURCE_ADMIN, 1));
        $this->assertSame(1, (int) $this->serviceSaleRow($saleId)['status']);
        $actions = array_column($this->plugin->getAuditEntries(\BuyCoursesPlugin::AUDIT_OBJECT_SERVICE_SALE, $saleId), 'action');
        $this->assertContains(\BuyCoursesPlugin::AUDIT_ACTION_SERVICE_SALE_COMPLETED, $actions);

        $this->assertTrue($this->plugin->completeServiceSale($saleId), 'idempotent');
        $this->assertSame(1, count(array_filter(
            array_column($this->plugin->getAuditEntries(\BuyCoursesPlugin::AUDIT_OBJECT_SERVICE_SALE, $saleId), 'action'),
            static fn ($a) => \BuyCoursesPlugin::AUDIT_ACTION_SERVICE_SALE_COMPLETED === $a
        )), 'the second call does not log a second completion');
    }

    public function testCompleteServiceSaleUnknown(): void
    {
        $this->assertFalse($this->plugin->completeServiceSale(999999));
        $this->assertNotSame('', $this->plugin->getLastServiceSaleError());
    }

    public function testCompleteServiceSaleInvoicesWhenEnabled(): void
    {
        $this->setPluginSettings(['tax_enable' => 'false', 'invoicing_enable' => 'true']);
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_invoices');
        $saleId = $this->makeServiceSale($this->makeService());

        $this->plugin->completeServiceSale($saleId);

        $this->assertSame(1, (int) $this->serviceSaleRow($saleId)['invoice']);
        $this->assertSame(1, (int) $this->plugin->getDataInvoice($saleId, 1)['is_service']);
    }

    public function testCancelServiceSaleAndUnknown(): void
    {
        $saleId = $this->makeServiceSale($this->makeService());

        $this->assertTrue($this->plugin->cancelServiceSale($saleId, \BuyCoursesPlugin::AUDIT_SOURCE_ADMIN, 1));
        $this->assertSame(-1, (int) $this->serviceSaleRow($saleId)['status']);
        $this->assertTrue($this->plugin->cancelServiceSale($saleId), 'already cancelled');
        $this->assertFalse($this->plugin->cancelServiceSale(999999));

        // PINNED QUIRK: a completed (paid) service sale can be cancelled and a cancelled one completed again,
        // there is no transition table.
        $done = $this->makeServiceSale($this->makeService(), ['status' => 1]);
        $this->assertTrue($this->plugin->cancelServiceSale($done));
        $this->assertSame(-1, (int) $this->serviceSaleRow($done)['status']);
        $this->assertTrue($this->plugin->completeServiceSale($done));
        $this->assertSame(1, (int) $this->serviceSaleRow($done)['status']);
    }

    public function testGetServiceSalesFiltersAndOrder(): void
    {
        $service = $this->makeService();
        $a = $this->makeServiceSale($service, ['buyer_id' => 2, 'status' => 0]);
        $b = $this->makeServiceSale($service, ['buyer_id' => 2, 'status' => 1]);
        $c = $this->makeServiceSale($service, ['buyer_id' => 1, 'status' => 1, 'node_id' => 3]);

        $mine = [$a, $b, $c];
        $ids = static fn (array $rows) => array_values(array_filter(
            array_map(static fn ($r) => (int) $r['id'], $rows),
            static fn ($i) => in_array($i, $mine, true)
        ));
        $this->assertSame([$c, $b, $a], $ids($this->plugin->getServiceSales()), 'newest first');
        // the status filter is driven by func_num_args(): an explicit 0 means "pending only", not "any"
        $this->assertSame([$a], $ids($this->plugin->getServiceSales(0, 0)));
        $this->assertSame([$b, $a], $ids($this->plugin->getServiceSales(2)));
        $this->assertSame([$b], $ids($this->plugin->getServiceSales(2, 1)));
        $this->assertSame([$c], $ids($this->plugin->getServiceSales(0, 1, 1, 3)));
        $this->assertSame(1, $this->plugin->getServiceSales(2, 1, 0, 0, 0, 0, 'count'), 'count with buyer 2 and status completed');
    }

    public function testGetServiceSaleReturnsBuyerOwnerAndServiceData(): void
    {
        $service = $this->makeService(['name' => 'bc_named', 'owner_id' => 1]);
        $saleId = $this->makeServiceSale($service, ['buyer_id' => 2]);

        $sale = $this->plugin->getServiceSale($saleId);

        $this->assertSame('bc_named', $sale['service']['name']);
        $this->assertSame('EUR', $sale['service']['currency']);
        $this->assertSame('EUR 100.00', $sale['service']['total_price']);
        $this->assertSame(2, (int) $sale['buyer']['id']);
        $this->assertSame('anon', $sale['buyer']['username']);
        $this->assertSame(1, (int) $sale['service']['owner']['id']);
        $this->assertSame([], $this->plugin->getServiceSale(0));
        $this->assertSame([], $this->plugin->getServiceSale(999999));
    }

    public function testCanUserAccessServiceInvoiceOnlyBuyer(): void
    {
        $saleId = $this->makeServiceSale($this->makeService(), ['buyer_id' => 2]);

        $this->assertTrue($this->plugin->canUserAccessInvoice($saleId, 1, 2));
        $this->assertFalse($this->plugin->canUserAccessInvoice($saleId, 1, 1));
        // the same id as a COURSE sale id must not open the service sale
        $this->assertFalse($this->plugin->canUserAccessInvoice($saleId, 0, 2));
    }

    public function testGatewayDataUpdateIsRestrictedToAllowedFields(): void
    {
        $saleId = $this->makeServiceSale($this->makeService());

        $this->assertFalse($this->plugin->updateServiceSaleGatewayData($saleId, ['price' => '0.01', 'buyer_id' => 1]));
        $this->assertFalse($this->plugin->updateServiceSaleGatewayData(0, ['status' => 1]));
        $this->assertTrue($this->plugin->updateServiceSaleGatewayData($saleId, ['gateway_subscription_id' => 'sub_bc1', 'price' => '0.01']));

        $row = $this->serviceSaleRow($saleId);
        $this->assertSame('sub_bc1', $row['gateway_subscription_id']);
        $this->assertSame('100.00', $row['price'], 'price is not an updatable gateway field');
    }

    public function testGatewayLookups(): void
    {
        $saleId = $this->makeServiceSale($this->makeService(), ['gateway_checkout_session_id' => 'cs_bc1', 'gateway_subscription_id' => 'sub_bc2', 'recurring_profile_id' => 'prof_bc3']);

        $this->assertSame($saleId, (int) $this->plugin->getServiceSaleFromGatewayCheckoutSessionId(' cs_bc1 ')['id']);
        $this->assertSame([], $this->plugin->getServiceSaleFromGatewayCheckoutSessionId(''));
        $this->assertSame($saleId, (int) $this->plugin->getServiceSaleFromGatewaySubscriptionId('sub_bc2')['id']);
        $this->assertSame($saleId, (int) $this->plugin->getServiceSaleFromGatewaySubscriptionId('prof_bc3')['id'], 'falls back to the recurring profile id');
        $this->assertSame([], $this->plugin->getServiceSaleFromGatewaySubscriptionId('sub_unknown'));
    }

    public function testGatewayEventIdempotency(): void
    {
        $saleId = $this->makeServiceSale($this->makeService());
        $other = $this->makeServiceSale($this->makeService());

        $this->assertFalse($this->plugin->wasGatewayEventProcessed($saleId, 'evt_1'));
        $this->assertTrue($this->plugin->markGatewayEventProcessed($saleId, 'evt_1'));
        $this->assertTrue($this->plugin->wasGatewayEventProcessed($saleId, 'evt_1'));
        $this->assertFalse($this->plugin->wasGatewayEventProcessed($saleId, 'evt_2'));
        $this->assertFalse($this->plugin->wasGatewayEventProcessed($other, 'evt_1'), 'the marker is per sale');
        $this->assertFalse($this->plugin->markGatewayEventProcessed($saleId, '  '));
    }

    public function testServiceCouponAppliedCounter(): void
    {
        $service = $this->makeService();
        $saleId = $this->makeServiceSale($service);
        $coupon = $this->makeCoupon('BC_CNT', 1, 10);
        $this->assertTrue($this->plugin->registerCouponServiceSale($saleId, $coupon));

        $this->assertSame(1, (int) $this->plugin->getServiceSaleCouponUsage($saleId)['applied_count']);
        $this->assertSame(2, $this->plugin->incrementServiceSaleCouponAppliedCount($saleId));
        $this->assertSame(3, $this->plugin->incrementServiceSaleCouponAppliedCount($saleId));
        $this->assertSame('BC_CNT', $this->plugin->getServiceSaleCouponCode($saleId));
        $this->assertSame(0, $this->plugin->incrementServiceSaleCouponAppliedCount($this->makeServiceSale($service)), 'no coupon on that sale');
        $this->assertFalse($this->plugin->registerCouponServiceSale(999999, $coupon));
        $this->assertSame([], $this->plugin->getServiceSaleCouponUsage(0));
    }

    public function testStoreServiceNormalizesThePayload(): void
    {
        $id = (int) $this->plugin->storeService([
            'name' => '  bc_<script>alert(1)</script>svc ',
            'price' => '12.345',
            'tax_perc' => '',
            'duration_days' => '10',
            'applies_to' => '1',
            'owner_id' => '1',
            'renewable' => 'on',
            'total_charges' => '-5',
            'visibility' => '',
            'active' => '1',
        ]);

        $row = $this->conn->fetchAssociative('SELECT * FROM plugin_buycourses_services WHERE id = ?', [$id]);
        $this->assertStringNotContainsString('<script', $row['name']);
        $this->assertSame('12.35', $row['price']);
        $this->assertNull($row['tax_perc']);
        $this->assertSame(10, (int) $row['duration_days']);
        $this->assertSame(1, (int) $row['renewable']);
        $this->assertSame(0, (int) $row['total_charges'], 'negative counters are clamped');
        $this->assertSame(0, (int) $row['visibility']);
    }

    public function testDeleteServiceAlsoDeletesItsSales(): void
    {
        $service = $this->makeService();
        $other = $this->makeService();
        $sale = $this->makeServiceSale($service, ['status' => 1]);
        $keep = $this->makeServiceSale($other);

        $this->plugin->deleteService($service);

        // PINNED QUIRK: the sales history (including COMPLETED, paid sales) of the service is erased with it.
        $this->assertSame([], $this->serviceSaleRow($sale));
        $this->assertNotEmpty($this->serviceSaleRow($keep));
        $this->assertSame([], $this->plugin->getService($service));
    }

    public function testGetServiceAppliesTax(): void
    {
        $this->setGlobalConfig(0, \BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SERVICES, 'IVA');
        $this->setPluginSettings(['tax_enable' => 'true']);
        $service = $this->makeService(['price' => '10.00', 'tax_perc' => 21]);

        $data = $this->plugin->getService($service);

        $this->assertSame(2.1, $data['tax_amount']);
        $this->assertEqualsWithDelta(12.1, $data['total_price'], 0.0000001);
        $this->assertSame('IVA', $data['tax_name']);
        $this->assertTrue($data['tax_enable']);
        $this->assertSame([], $this->plugin->getService(0));
    }
}
