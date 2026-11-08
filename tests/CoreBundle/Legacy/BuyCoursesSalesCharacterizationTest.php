<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

/**
 * Characterization of the course/session sale flow of BuyCoursesPlugin: registration with tax and coupons,
 * status transitions, sale listings, invoices (fixtures prefix bc_).
 */
final class BuyCoursesSalesCharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    private int $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selectCurrency('EUR');
        $this->setGlobalConfig(0, 0);
        $this->setPluginSettings(['tax_enable' => 'false']);
        $this->loginAs(2);
        $this->item = $this->makeItem(1, 100.0);
    }

    private function couponForCourse(string $code, int $type, int $amount, array $opts = []): int
    {
        $id = $this->makeCoupon($code, $type, $amount, $opts['start'] ?? '2020-01-01 00:00:00', $opts['end'] ?? '2099-12-31 23:59:59', $opts['active'] ?? 1);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $id, 'product_type' => 1, 'product_id' => $opts['product'] ?? 1]);

        return $id;
    }

    public function testRegisterSaleRejectsUnknownPaymentType(): void
    {
        $this->assertNull($this->plugin->registerSale($this->item, 99));
        $this->assertNull($this->plugin->registerSale($this->item, 0));
    }

    public function testRegisterSaleRejectsUnknownItemAndMissingCourse(): void
    {
        $this->assertNull($this->plugin->registerSale(999999, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER));
        $ghost = $this->makeItem(987654, 10.0);
        $this->assertNull($this->plugin->registerSale($ghost, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER), 'item of a course that does not exist');
    }

    public function testRegisterSalePlain(): void
    {
        $saleId = $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER);
        $sale = $this->saleRow((int) $saleId);

        $this->assertSame(2, (int) $sale['user_id']);
        $this->assertSame(1, (int) $sale['product_id']);
        $this->assertSame(1, (int) $sale['product_type']);
        $this->assertSame('AI Act', $sale['product_name']);
        $this->assertSame('100.00', $sale['price']);
        $this->assertSame(0, (int) $sale['status']);
        $this->assertSame(2, (int) $sale['payment_type']);
        $this->assertSame('0.00', $sale['discount_amount']);
        $this->assertSame('0.00', $sale['price_without_discount']);
        $this->assertSame(0, (int) $sale['invoice']);
        $this->assertMatchesRegularExpression('/^2-1-1-[A-Za-z0-9]{6}$/', $sale['reference']);
        $this->assertNull($sale['price_without_tax']);
        $this->assertNull($sale['tax_perc']);
        $this->assertSame('missing_vat_country_information', $sale['vat_treatment']);
    }

    public function testRegisterSaleStoresTheBuyerFromTheSessionNotFromArguments(): void
    {
        $this->loginAs(1);
        $saleId = $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL);
        $this->assertSame(1, (int) $this->saleRow((int) $saleId)['user_id']);
    }

    public function testRegisterSaleAddsTaxOnTheDiscountedPriceWithRounding(): void
    {
        $this->setGlobalConfig(10, \BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);
        $item = $this->makeItem(2, 33.33, 21);

        $sale = $this->saleRow((int) $this->plugin->registerSale($item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER));

        // 33.33 * 21% = 6.9993 -> 7.00 ; total 40.33
        $this->assertSame('40.33', $sale['price']);
        $this->assertSame('33.33', $sale['price_without_tax']);
        $this->assertSame(21, (int) $sale['tax_perc']);
        $this->assertSame('7.00', $sale['tax_amount']);
    }

    public function testRegisterSaleGlobalTaxWhenItemHasNone(): void
    {
        $this->setGlobalConfig(10, \BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE);
        $this->setPluginSettings(['tax_enable' => 'true']);

        $sale = $this->saleRow((int) $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER));

        $this->assertSame('110.00', $sale['price']);
        $this->assertSame('100.00', $sale['price_without_tax']);
        $this->assertSame('10.00', $sale['tax_amount']);
    }

    public function testRegisterSalePercentageCoupon(): void
    {
        $item = $this->makeItem(2, 80.0);
        $this->conn->insert('plugin_buycourses_coupon_rel_item', ['coupon_id' => $c = $this->makeCoupon('BC_PCT', 1, 15), 'product_type' => 1, 'product_id' => 2]);

        $sale = $this->saleRow((int) $this->plugin->registerSale($item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, $c));

        $this->assertSame('68.00', $sale['price']);
        $this->assertSame('80.00', $sale['price_without_discount']);
        $this->assertSame('12.00', $sale['discount_amount']);
    }

    public function testRegisterSaleAmountCoupon(): void
    {
        $c = $this->couponForCourse('BC_AMT', 2, 30);
        $sale = $this->saleRow((int) $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, $c));

        $this->assertSame('70.00', $sale['price']);
        $this->assertSame('100.00', $sale['price_without_discount']);
        $this->assertSame('30.00', $sale['discount_amount']);
    }

    public function testRegisterSaleAmountCouponAbovePrice(): void
    {
        $c = $this->couponForCourse('BC_BIG', 2, 150);
        $sale = $this->saleRow((int) $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, $c));

        // PINNED QUIRK: no cap, the sale is stored with a negative price (the service sale caps the discount).
        $this->assertSame('-50.00', $sale['price']);
        $this->assertSame('150.00', $sale['discount_amount']);
    }

    public function testRegisterSaleIgnoresExpiredFutureAndForeignCoupons(): void
    {
        $expired = $this->couponForCourse('BC_OLD', 2, 30, ['start' => '2020-01-01 00:00:00', 'end' => '2020-12-31 00:00:00']);
        $future = $this->couponForCourse('BC_NEW', 2, 30, ['start' => '2098-01-01 00:00:00']);
        $foreign = $this->couponForCourse('BC_OTHER', 2, 30, ['product' => 2]);

        foreach ([$expired, $future, $foreign] as $couponId) {
            $sale = $this->saleRow((int) $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, $couponId));
            $this->assertSame('100.00', $sale['price'], 'coupon '.$couponId.' must not apply');
            $this->assertSame('0.00', $sale['discount_amount']);
        }
    }

    public function testRegisterSaleAppliesADisabledCoupon(): void
    {
        $c = $this->couponForCourse('BC_OFF', 2, 30, ['active' => 0]);
        $sale = $this->saleRow((int) $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER, $c));

        // PINNED QUIRK: the "active" flag of the coupon is never checked when a coupon is redeemed.
        $this->assertSame('70.00', $sale['price']);
    }

    public function testReferenceLookupAndUpdate(): void
    {
        $saleId = (int) $this->plugin->registerSale($this->item, \BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER);
        $this->plugin->updateSaleReference($saleId, 'bc_custom_ref');

        $this->assertSame($saleId, (int) $this->plugin->getSaleFromReference('bc_custom_ref')['id']);
        $this->assertSame('bc_custom_ref', $this->plugin->getSale($saleId)['reference']);
        $this->assertSame([], $this->plugin->getSaleFromReference('bc_nope'));
        $this->assertSame([], $this->plugin->getSale(999999));
    }

    public function testCompleteSaleSubscribesTheBuyerAndIsIdempotent(): void
    {
        $saleId = $this->makeSale(['user_id' => 2, 'product_id' => 1]);
        $before = (int) $this->conn->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = 2 AND c_id = 1');

        $this->assertTrue($this->plugin->completeSale($saleId));
        $this->assertSame(1, (int) $this->saleRow($saleId)['status']);
        $this->assertSame($before + 1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = 2 AND c_id = 1'));

        $this->assertTrue($this->plugin->completeSale($saleId), 'second call is a no-op');
        $this->assertSame($before + 1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM course_rel_user WHERE user_id = 2 AND c_id = 1'));
    }

    public function testCompleteSaleOfUnknownCourseStaysPending(): void
    {
        $saleId = $this->makeSale(['user_id' => 2, 'product_id' => 987654]);

        $this->assertFalse($this->plugin->completeSale($saleId));
        $this->assertSame(0, (int) $this->saleRow($saleId)['status']);
    }

    public function testCompleteSaleCreatesAnInvoiceWhenInvoicingIsEnabled(): void
    {
        $this->setPluginSettings(['tax_enable' => 'false', 'invoicing_enable' => 'true']);
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_invoices');
        $saleId = $this->makeSale(['user_id' => 2, 'product_id' => 1]);

        $this->assertTrue($this->plugin->completeSale($saleId));

        $this->assertSame(1, (int) $this->saleRow($saleId)['invoice']);
        $invoice = $this->plugin->getDataInvoice($saleId, 0);
        $this->assertSame(1, (int) $invoice['num_invoice']);
        $this->assertSame(date('Y'), (string) $invoice['year']);
        $this->assertSame(date('Y').'/1', $this->plugin->getNumInvoice($saleId, 0));
    }

    public function testInvoiceNumberUsesNextNumberThenResetsIt(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_invoices');
        $this->plugin->getGlobalParameters();
        $this->conn->executeStatement("UPDATE plugin_buycourses_global_config SET next_number_invoice = 500, invoice_series = 'BC' WHERE id = 1");
        $a = $this->makeSale();
        $b = $this->makeSale();

        $this->plugin->setInvoice($a);
        $this->plugin->setInvoice($b);

        $this->assertSame('BC'.date('Y').'/500', $this->plugin->getNumInvoice($a, 0));
        // the second invoice restarts from MAX(num_invoice) + 1 of the year
        $this->assertSame('BC'.date('Y').'/501', $this->plugin->getNumInvoice($b, 0));
        $this->assertSame(0, (int) $this->plugin->getGlobalParameters()['next_number_invoice']);
    }

    public function testGetNumInvoiceDashWhenNoInvoice(): void
    {
        $this->assertSame('-', $this->plugin->getNumInvoice($this->makeSale(), 0));
    }

    public function testCancelSaleTransitions(): void
    {
        $pending = $this->makeSale(['user_id' => 2]);
        $this->plugin->cancelSale($pending);
        $this->assertSame(-1, (int) $this->saleRow($pending)['status']);

        // PINNED QUIRK: no guard on the previous status, a completed sale can be cancelled
        // (the subscription it granted is not revoked) and a cancelled sale can be completed again.
        $completed = $this->makeSale(['status' => 1]);
        $this->plugin->cancelSale($completed);
        $this->assertSame(-1, (int) $this->saleRow($completed)['status']);
        $this->assertTrue($this->plugin->completeSale($pending));
        $this->assertSame(1, (int) $this->saleRow($pending)['status']);
    }

    public function testSaleListByStatusJoinsBuyerAndCurrencyNewestFirst(): void
    {
        $a = $this->makeSale(['status' => 0, 'user_id' => 1]);
        $b = $this->makeSale(['status' => 0, 'user_id' => 2]);
        $this->makeSale(['status' => 1]);

        $rows = array_values(array_filter(
            $this->plugin->getSaleListByStatus(0),
            static fn ($r) => str_starts_with($r['reference'], 'bc_ref_')
        ));

        $this->assertSame([$b, $a], array_map(static fn ($r) => (int) $r['id'], $rows));
        $this->assertSame('EUR', $rows[0]['iso_code']);
        $this->assertSame('anon', strtolower((string) $this->conn->fetchOne('SELECT username FROM user WHERE id = 2')));
        $this->assertArrayHasKey('email', $rows[0]);
    }

    public function testSaleListByUserIdOnlyCompletedOfThatUser(): void
    {
        $done = $this->makeSale(['status' => 1, 'user_id' => 2]);
        $this->makeSale(['status' => 0, 'user_id' => 2]);
        $this->makeSale(['status' => -1, 'user_id' => 2]);
        $this->makeSale(['status' => 1, 'user_id' => 1]);

        $rows = array_values($this->plugin->getSaleListByUserId(2));
        $mine = array_values(array_filter($rows, static fn ($r) => str_starts_with($r['reference'], 'bc_ref_')));

        $this->assertCount(1, $mine);
        $this->assertSame($done, (int) $mine[0]['id']);
        $this->assertSame([], $this->plugin->getSaleListByUserId(0));
    }

    public function testSaleListByPaymentTypeOnlyCompleted(): void
    {
        $ok = $this->makeSale(['status' => 1, 'payment_type' => 5, 'reference' => 'bc_ref_stripe_ok']);
        $this->makeSale(['status' => 0, 'payment_type' => 5, 'reference' => 'bc_ref_stripe_pending']);
        $this->makeSale(['status' => 1, 'payment_type' => 1, 'reference' => 'bc_ref_paypal']);

        $refs = array_column($this->plugin->getSaleListByPaymentType(5), 'reference');
        $this->assertContains('bc_ref_stripe_ok', $refs);
        $this->assertNotContains('bc_ref_stripe_pending', $refs);
        $this->assertNotContains('bc_ref_paypal', $refs);
        $this->assertGreaterThan(0, $ok);
    }

    public function testSaleListByUserTermMatchesUsernameFirstnameLastname(): void
    {
        $sale = $this->makeSale(['user_id' => 1]);

        $this->assertContains($sale, array_map('intval', array_column($this->plugin->getSaleListByUser('adm'), 'id')));
        $this->assertSame([], $this->plugin->getSaleListByUser('   '));
        $this->assertSame([], $this->plugin->getSaleListByUser('zzz_no_such_user'));
    }

    public function testSaleListByEmail(): void
    {
        $this->conn->executeStatement("UPDATE user SET email = 'bc_buyer@example.org' WHERE id = 2");
        $sale = $this->makeSale(['user_id' => 2]);

        $rows = $this->plugin->getSaleListByEmail('bc_buyer@');
        $this->assertSame([$sale], array_map('intval', array_column($rows, 'id')));
        $this->assertSame([], $this->plugin->getSaleListByEmail(''));
    }

    public function testSaleListByDateIsInclusive(): void
    {
        $in = $this->makeSale(['date' => '2026-03-10 12:00:00']);
        $edgeStart = $this->makeSale(['date' => '2026-03-01 00:00:00']);
        $edgeEnd = $this->makeSale(['date' => '2026-03-31 23:59:59']);
        $this->makeSale(['date' => '2026-02-28 23:59:59']);
        $this->makeSale(['date' => '2026-04-01 00:00:00']);

        $ids = array_map('intval', array_column($this->plugin->getSaleListByDate('2026-03-01 00:00:00', '2026-03-31 23:59:59'), 'id'));
        sort($ids);
        $expected = [$in, $edgeStart, $edgeEnd];
        sort($expected);
        $this->assertSame($expected, $ids);
        $this->assertSame([], $this->plugin->getSaleListByDate('', '2026-03-31'));
    }

    public function testCanUserAccessInvoiceOnlyOwner(): void
    {
        $sale = $this->makeSale(['user_id' => 2]);

        $this->assertTrue($this->plugin->canUserAccessInvoice($sale, 0, 2));
        $this->assertFalse($this->plugin->canUserAccessInvoice($sale, 0, 1), 'another (non admin session) user');
        $this->assertFalse($this->plugin->canUserAccessInvoice($sale, 0, 0));
        $this->assertFalse($this->plugin->canUserAccessInvoice(0, 0, 2));
        $this->assertFalse($this->plugin->canUserAccessInvoice(999999, 0, 2));
    }

    public function testCanUserAccessInvoiceUsesTheSessionUserByDefault(): void
    {
        $sale = $this->makeSale(['user_id' => 2]);
        $this->loginAs(2);
        $this->assertTrue($this->plugin->canUserAccessInvoice($sale));
        $this->loginAs(1);
        $this->assertFalse($this->plugin->canUserAccessInvoice($sale), 'no security token: not a platform admin here');
        $this->loginAs(0);
        $this->assertFalse($this->plugin->canUserAccessInvoice($sale));
    }

    public function testInvoiceUrlCarriesIdsAsIntegers(): void
    {
        $this->assertStringEndsWith('BuyCourses/src/invoice.php?sale_id=12&is_service=1', $this->plugin->getInvoiceUrl(12, 1));
    }

    public function testCouponSaleLink(): void
    {
        $c = $this->couponForCourse('BC_LINK', 2, 5);
        $sale = $this->makeSale();

        $this->assertTrue($this->plugin->registerCouponSale($sale, $c));
        $this->assertSame('BC_LINK', $this->plugin->getSaleCouponCode($sale));
        $this->assertFalse($this->plugin->registerCouponSale(999999, $c), 'unknown sale');
        $this->assertSame('', $this->plugin->getSaleCouponCode(999999));
    }

    public function testGetDataSaleInvoiceForCourseSale(): void
    {
        $sale = $this->makeSale(['user_id' => 2, 'product_name' => 'bc_named']);
        $data = $this->plugin->getDataSaleInvoice($sale, 0);
        $this->assertSame('bc_named', $data['product_name']);
        $this->assertSame(2, (int) $data['user_id']);
    }
}
