<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;

/**
 * The amount returned (and signed) by the Redsys / Cecabank gateways must equal the price of the sale it
 * completes: a valid signature only proves the gateway said "paid X", not that X is what the sale costs.
 * Amounts are compared as integer cents, never as floats. Tags start with "bg_".
 */
final class BuyCoursesGatewayAmountTest extends BuyCoursesCharacterizationTestCase
{
    private const SRC = __DIR__.'/../../../public/plugin/BuyCourses/src/';

    public function testPriceToCentsIsExactForPricesThatBreakFloatMultiplication(): void
    {
        // 19.99 * 100 = 1998.9999999999998 as a float.
        self::assertSame(1999, BuyCoursesPlugin::priceToCents('19.99'));
        self::assertSame(1999, BuyCoursesPlugin::priceToCents(19.99));
        self::assertSame(10000, BuyCoursesPlugin::priceToCents('100.00'));
        self::assertSame(1, BuyCoursesPlugin::priceToCents('0.01'));
        self::assertSame(2905, BuyCoursesPlugin::priceToCents(29.05));
    }

    public function testMatchingAmountIsAccepted(): void
    {
        $sale = ['price' => '19.99'];
        self::assertTrue(BuyCoursesPlugin::gatewayAmountMatchesSale('1999', $sale));
        self::assertTrue(BuyCoursesPlugin::gatewayAmountMatchesSale(' 1999 ', $sale));
        self::assertTrue(BuyCoursesPlugin::gatewayAmountMatchesSale(1999, $sale));
    }

    /**
     * @dataProvider wrongAmounts
     */
    public function testAnyOtherAmountIsRefused(string|int|float|null $received): void
    {
        self::assertFalse(BuyCoursesPlugin::gatewayAmountMatchesSale($received, ['price' => '19.99']));
    }

    public static function wrongAmounts(): iterable
    {
        yield 'one cent less' => ['1998'];
        yield 'one cent more' => ['2000'];
        yield 'a tenth of the price' => ['199'];
        yield 'zero' => ['0'];
        yield 'empty' => [''];
        yield 'missing' => [null];
        yield 'decimal notation instead of cents' => ['19.99'];
        yield 'not a number' => ['abc'];
        yield 'negative' => ['-1999'];
        yield 'float' => [1999.0];
    }

    public function testFreeSaleNeverMatchesAnAmount(): void
    {
        self::assertFalse(BuyCoursesPlugin::gatewayAmountMatchesSale('0', ['price' => '0.00']));
    }

    /**
     * @dataProvider gatewayPages
     */
    public function testGatewayPagesCheckTheAmountBeforeCompletingTheSale(string $page): void
    {
        $source = (string) file_get_contents(self::SRC.$page);

        $check = strpos($source, 'gatewayAmountMatchesSale(');
        $complete = strpos($source, '->completeSale(');

        self::assertNotFalse($check, $page.' must compare the signed amount with the sale price');
        self::assertNotFalse($complete);
        self::assertLessThan($complete, $check, 'the amount is checked before the sale is completed');
    }

    public static function gatewayPages(): iterable
    {
        yield ['tpv_response.php'];
        yield ['cecabank_response.php'];
    }
}
