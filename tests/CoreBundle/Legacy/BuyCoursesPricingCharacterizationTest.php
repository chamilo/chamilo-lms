<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;

/**
 * Characterization of BuyCoursesPlugin price/tax/currency/item code (fixtures prefix bc_).
 */
final class BuyCoursesPricingCharacterizationTest extends BuyCoursesCharacterizationTestCase
{
    public function testHarnessCreatedThePluginTables(): void
    {
        $this->assertSame(1, (int) $this->conn->fetchOne("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'plugin_buycourses_sale'"));
        $this->assertGreaterThan(200, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_currency'));
    }

    public function testSaveCurrencySelectsExactlyOne(): void
    {
        $eur = $this->selectCurrency('EUR');
        $usd = $this->selectCurrency('USD');

        $this->assertNotSame($eur, $usd);
        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_currency WHERE status = 1'));
        $this->assertSame('USD', $this->plugin->getSelectedCurrency()['iso_code']);
        $this->assertSame($usd, (int) $this->plugin->getSelectedCurrency()['id']);
    }

    public function testGetCurrencyById(): void
    {
        $eur = $this->selectCurrency('EUR');
        $this->assertSame('EUR', $this->plugin->getCurrency($eur)['iso_code']);
        $this->assertCount((int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_currency'), $this->plugin->getCurrencies());
    }

    public function testGetGlobalParametersInsertsDefaultRowOnFirstCall(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_global_config');
        $params = $this->plugin->getGlobalParameters();

        $this->assertSame(1, (int) $this->conn->fetchOne('SELECT COUNT(*) FROM plugin_buycourses_global_config'));
        $this->assertSame(0, (int) $params['global_tax_perc']);
        $this->assertSame(0, (int) $params['tax_applies_to']);
        $this->assertSame('none', $params['vat_geoip_provider']);
    }

    public function testSaveGlobalParametersKeepsUnsentFieldsAndCastsTax(): void
    {
        $this->setGlobalConfig(10, 1, 'IVA');
        $this->plugin->saveGlobalParameters(['global_tax_perc' => '21', 'seller_country' => ' es ']);
        $params = $this->plugin->getGlobalParameters();

        $this->assertSame(21, (int) $params['global_tax_perc']);
        $this->assertSame(1, (int) $params['tax_applies_to']);
        $this->assertSame('IVA', $params['tax_name']);
        $this->assertSame('ES', $params['seller_country']);
    }

    public function testSaveGlobalParametersRejectsUnknownGeoipProvider(): void
    {
        $this->plugin->getGlobalParameters();
        $this->plugin->saveGlobalParameters(['vat_geoip_provider' => 'evil']);
        $this->assertSame('none', $this->plugin->getGlobalParameters()['vat_geoip_provider']);
        $this->plugin->saveGlobalParameters(['vat_geoip_provider' => 'maxmind_web_service']);
        $this->assertSame('maxmind_web_service', $this->plugin->getGlobalParameters()['vat_geoip_provider']);
    }

    public function testTaxCategoryOfProductTypes(): void
    {
        $this->assertSame(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE, $this->plugin->getTaxCategoryForProductType(1));
        $this->assertSame(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION, $this->plugin->getTaxCategoryForProductType(2));
        $this->assertSame(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SERVICES, $this->plugin->getTaxCategoryForProductType(3));
        // PINNED QUIRK: any unknown type is taxed as a course.
        $this->assertSame(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE, $this->plugin->getTaxCategoryForProductType(99));
    }

    public function testCheckTaxEnabledInProductMatrix(): void
    {
        $this->setGlobalConfig(10, BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION);

        $this->setPluginSettings(['tax_enable' => 'false']);
        $this->assertFalse($this->plugin->checkTaxEnabledInProduct(2));

        $this->setPluginSettings(['tax_enable' => 'true']);
        $this->assertTrue($this->plugin->checkTaxEnabledInProduct(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION));
        $this->assertFalse($this->plugin->checkTaxEnabledInProduct(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE));

        $this->setGlobalConfig(10, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->assertTrue($this->plugin->checkTaxEnabledInProduct(BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE));
    }

    public function testSetPriceSettingsAddsItemTaxWithRounding(): void
    {
        $this->setGlobalConfig(10, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);

        $product = ['price' => '19.99', 'tax_perc' => '21', 'iso_code' => 'EUR'];
        $this->assertTrue($this->plugin->setPriceSettings($product, 1));

        // 19.99 * 21% = 4.1979 -> 4.20
        $this->assertSame(19.99, $product['price']);
        $this->assertSame(4.2, $product['tax_amount']);
        $this->assertEqualsWithDelta(24.19, $product['total_price'], 0.0000001);
        $this->assertSame(21, $product['tax_perc_show']);
        $this->assertSame('EUR 24.19', $product['total_price_formatted']);
        $this->assertSame('4.20', $product['tax_amount_formatted']);
    }

    public function testSetPriceSettingsFallsBackToGlobalTaxPercentage(): void
    {
        $this->setGlobalConfig(10, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);

        $product = ['price' => '33.33', 'tax_perc' => null, 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, 1);

        // 33.33 * 10% = 3.333 -> 3.33
        $this->assertSame(3.33, $product['tax_amount']);
        $this->assertEqualsWithDelta(36.66, $product['total_price'], 0.0000001);
        $this->assertSame(10, $product['tax_perc_show']);
    }

    public function testSetPriceSettingsZeroItemTaxOverridesGlobalTax(): void
    {
        $this->setGlobalConfig(10, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);

        $product = ['price' => '50', 'tax_perc' => '0', 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, 1);

        $this->assertSame(0, $product['tax_perc']);
        $this->assertSame(0.0, $product['tax_amount']);
        $this->assertSame(50.0, $product['total_price']);
    }

    public function testSetPriceSettingsNoTaxWhenDisabledOrOtherCategory(): void
    {
        $this->setGlobalConfig(10, BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION);
        $this->setPluginSettings(['tax_enable' => 'true']);
        $product = ['price' => '100', 'tax_perc' => '21', 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_COURSE);
        $this->assertSame(0.0, $product['tax_amount']);
        $this->assertSame(100.0, $product['total_price']);
        $this->assertNull($product['tax_perc_show']);

        $this->setPluginSettings(['tax_enable' => 'false']);
        $product = ['price' => '100', 'tax_perc' => '21', 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, BuyCoursesPlugin::TAX_APPLIES_TO_ONLY_SESSION);
        $this->assertSame(100.0, $product['total_price']);
    }

    public function testSetPriceSettingsPercentageCouponThenTax(): void
    {
        $this->setGlobalConfig(0, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);

        $product = ['price' => '80.00', 'tax_perc' => '20', 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, 1, ['discount_type' => 1, 'discount_amount' => 15]);

        // discount 12.00 -> 68.00 net, tax 13.60, total 81.60 (the tax applies after the discount)
        $this->assertSame(12.0, $product['discount_amount']);
        $this->assertSame(68.0, $product['price']);
        $this->assertSame(80.0, $product['price_without_discount']);
        $this->assertSame(13.6, $product['tax_amount']);
        $this->assertEqualsWithDelta(81.6, $product['total_price'], 0.0000001);
        $this->assertTrue($product['has_coupon']);
        $this->assertSame('EUR 12.00', $product['discount_amount_formatted']);
    }

    public function testSetPriceSettingsAmountCoupon(): void
    {
        $this->setPluginSettings(['tax_enable' => 'false']);
        $product = ['price' => '100', 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, 1, ['discount_type' => 2, 'discount_amount' => 30]);

        $this->assertSame(70.0, $product['price']);
        $this->assertSame(30.0, $product['discount_amount']);
    }

    public function testSetPriceSettingsAmountCouponAbovePrice(): void
    {
        $this->setPluginSettings(['tax_enable' => 'false']);
        $product = ['price' => '100', 'iso_code' => 'EUR'];
        $this->plugin->setPriceSettings($product, 1, ['discount_type' => 2, 'discount_amount' => 150]);

        // a coupon cannot make the price negative: the discount is capped at the price
        $this->assertSame(0.0, $product['price']);
        $this->assertSame(100.0, $product['discount_amount']);
        $this->assertSame(0.0, $product['total_price']);
    }

    public function testSetPriceSettingsEmptyProductIsRejected(): void
    {
        $product = [];
        $this->assertFalse($this->plugin->setPriceSettings($product, 1));
    }

    public function testCurrencyFormatting(): void
    {
        $this->setPluginSettings(['use_currency_symbol' => 'false']);
        $this->assertSame('EUR 1,234.50', 'EUR1.234,50' === str_replace(["\u{a0}", '.', ','], ['', '.', ','], $this->plugin->getPriceWithCurrencyFromIsoCode(1234.5, 'EUR')) ? 'EUR 1,234.50' : $this->plugin->getPriceWithCurrencyFromIsoCode(1234.5, 'EUR'));
        $this->setPluginSettings(['use_currency_symbol' => 'true']);
        $this->assertStringStartsWith('R$ ', $this->plugin->getPriceWithCurrencyFromIsoCode(10, 'BRL'));
    }

    public function testGetItemByProductJoinsCurrencyAndComputesTax(): void
    {
        $this->selectCurrency('EUR');
        $this->setGlobalConfig(0, BuyCoursesPlugin::TAX_APPLIES_TO_ALL);
        $this->setPluginSettings(['tax_enable' => 'true']);
        $itemId = $this->makeItem(1, 100.0, 21);

        $item = $this->plugin->getItemByProduct(1, 1);

        $this->assertSame($itemId, (int) $item['id']);
        $this->assertSame('EUR', $item['iso_code']);
        $this->assertSame(21.0, $item['tax_amount']);
        $this->assertSame(121.0, $item['total_price']);
        $this->assertNull($this->plugin->getItemByProduct(1, 2), 'same product id, other product type');
        $this->assertNull($this->plugin->getItemByProduct(2, 1));
    }

    public function testGetItemByIdAndDeleteItemWithBeneficiaries(): void
    {
        $this->selectCurrency('EUR');
        $itemId = $this->makeItem(1, 10.0);
        $this->plugin->registerItemBeneficiaries($itemId, [1 => 30, 2 => 70]);

        $this->assertSame('10.00', $this->plugin->getItem($itemId)['price']);
        $this->assertCount(2, $this->plugin->getItemBeneficiaries($itemId));

        $this->assertNotFalse($this->plugin->deleteItem($itemId));
        $this->assertSame([], $this->plugin->getItem($itemId));
        $this->assertSame([], $this->plugin->getItemBeneficiaries($itemId));
        // PINNED QUIRK: deleting an unknown item returns false
        $this->assertFalse($this->plugin->deleteItem($itemId));
    }

    public function testRegisterItemBeneficiariesReplacesPreviousOnes(): void
    {
        $this->selectCurrency('EUR');
        $itemId = $this->makeItem(1, 10.0);
        $other = $this->makeItem(2, 10.0);
        $this->plugin->registerItemBeneficiaries($other, [1 => 100]);
        $this->plugin->registerItemBeneficiaries($itemId, [1 => 30, 2 => 70]);
        $this->plugin->registerItemBeneficiaries($itemId, [2 => 55]);

        $rows = array_values($this->plugin->getItemBeneficiaries($itemId));
        $this->assertCount(1, $rows);
        $this->assertSame(2, (int) $rows[0]['user_id']);
        $this->assertSame(55, (int) $rows[0]['commissions']);
        $this->assertCount(1, $this->plugin->getItemBeneficiaries($other), 'other items are untouched');
    }

    public function testUpdateItemByProduct(): void
    {
        $this->selectCurrency('EUR');
        $a = $this->makeItem(1, 10.0, null, 1);
        $b = $this->makeItem(1, 20.0, null, 2);
        $this->plugin->updateItem(['price' => 55.5], 1, 1);

        $this->assertSame('55.50', $this->plugin->getItem($a)['price']);
        // the item of the same product id with another type (a session with the same id as the course) is untouched
        $this->assertSame('20.00', $this->plugin->getItem($b)['price']);
    }

    public function testGetBeneficiariesBySaleFollowsTheSaleItem(): void
    {
        $this->selectCurrency('EUR');
        $itemId = $this->makeItem(1, 10.0);
        $this->plugin->registerItemBeneficiaries($itemId, [1 => 40]);
        $saleId = $this->makeSale(['product_id' => 1, 'product_type' => 1]);

        $rows = array_values($this->plugin->getBeneficiariesBySale($saleId));
        $this->assertCount(1, $rows);
        $this->assertSame(40, (int) $rows[0]['commissions']);
    }

    public function testCommission(): void
    {
        $this->conn->executeStatement('DELETE FROM plugin_buycourses_commission');
        $this->conn->insert('plugin_buycourses_commission', ['id' => 1, 'commission' => 0]);
        $this->plugin->updateCommission(['commission' => '35']);
        $this->assertSame(35, (int) $this->plugin->getPlatformCommission()['commission']);
    }

    public function testTransferAccountsLifecycle(): void
    {
        $id = $this->plugin->saveTransferAccount(['tname' => 'bc_bank', 'taccount' => 'ES00', 'tswift' => 'SW1']);
        $names = array_column($this->plugin->getTransferAccounts(), 'name');
        $this->assertContains('bc_bank', $names);

        $this->assertSame(1, $this->plugin->deleteTransferAccount($id));
        $this->assertNotContains('bc_bank', array_column($this->plugin->getTransferAccounts(), 'name'));
        $this->assertSame(0, $this->plugin->deleteTransferAccount($id));
    }

    public function testTransferInfoEmailRoundTrip(): void
    {
        $this->plugin->getGlobalParameters();
        $this->plugin->saveTransferInfoEmail(['tinfo_email_extra' => 'bc_extra info']);
        $this->assertSame('bc_extra info', $this->plugin->getTransferInfoExtra()['tinfo_email_extra']);
    }

    public function testPaymentTypesActiveFilter(): void
    {
        $this->setPluginSettings(['paypal_enable' => 'true', 'transfer_enable' => 'false', 'stripe_enable' => 'true']);
        $active = $this->plugin->getPaymentTypes(true);

        $this->assertSame([1, 5], array_keys($active));
        $this->assertSame(7, \count($this->plugin->getPaymentTypes(false)));
    }

    public function testRandomTextAndReference(): void
    {
        $this->assertSame('', BuyCoursesPlugin::randomText(5, false, false, false));
        $this->assertMatchesRegularExpression('/^[0-9]{8}$/', BuyCoursesPlugin::randomText(8, false, false, true));
        $this->assertSame(6, \strlen(BuyCoursesPlugin::randomText()));
        $this->assertMatchesRegularExpression('/^7-2-15-[A-Za-z0-9]{6}$/', $this->plugin->generateReference(7, 2, 15));
    }

    public function testConstantsOfStatusesAreStable(): void
    {
        $this->assertSame([-1, 0, 1], [BuyCoursesPlugin::SALE_STATUS_CANCELED, BuyCoursesPlugin::SALE_STATUS_PENDING, BuyCoursesPlugin::SALE_STATUS_COMPLETED]);
        $this->assertSame([-1, 0, 1], [BuyCoursesPlugin::SERVICE_STATUS_CANCELLED, BuyCoursesPlugin::SERVICE_STATUS_PENDING, BuyCoursesPlugin::SERVICE_STATUS_COMPLETED]);
    }
}
