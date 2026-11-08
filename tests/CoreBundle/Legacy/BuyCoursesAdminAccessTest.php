<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;
use ChamiloSession;

/**
 * Access control of the BuyCourses pages (public/plugin/BuyCourses/src).
 *
 * The pages are legacy pages, so CsrfProtectionListener does not cover them: completing a sale (= giving the
 * product away without payment) or cancelling one from a bare link was a CSRF hole. The decision helper lives in
 * admin_token.lib.php (behavioural tests); the pages are procedural, so their guards are pinned at source level
 * (the guard must come BEFORE the first state change). Tags start with "bp_".
 */
final class BuyCoursesAdminAccessTest extends BuyCoursesCharacterizationTestCase
{
    private const SRC = __DIR__.'/../../../public/plugin/BuyCourses/src/';

    private array $savedRequest = [];

    protected function setUp(): void
    {
        parent::setUp();
        require_once self::SRC.'admin_token.lib.php';
        $this->savedRequest = $_REQUEST;
        $_REQUEST = [];
        ChamiloSession::erase('buycourses_admin_sec_token');
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->savedRequest;
        parent::tearDown();
    }

    public function testTokenIsStableForTheSessionAndAcceptedOnlyWhenSubmitted(): void
    {
        $token = buycoursesAdminToken();
        self::assertNotSame('', $token);
        // Never rotated by a request: a second tab, or a page that renders again after saving, keeps working.
        self::assertSame($token, buycoursesAdminToken());

        self::assertFalse(buycoursesAdminTokenIsValid(), 'no token in the request');

        $_REQUEST[buycoursesAdminTokenName()] = 'forged';
        self::assertFalse(buycoursesAdminTokenIsValid(), 'wrong token');

        $_REQUEST[buycoursesAdminTokenName()] = '';
        self::assertFalse(buycoursesAdminTokenIsValid(), 'empty token');

        $_REQUEST[buycoursesAdminTokenName()] = $token;
        self::assertTrue(buycoursesAdminTokenIsValid());
        self::assertTrue(buycoursesAdminTokenIsValid(), 'checking does not consume the token');
    }

    public function testEmptySessionNeverValidatesAnEmptyToken(): void
    {
        // PINNED: an attacker's request without session token must not pass because "" === "".
        $_REQUEST[buycoursesAdminTokenName()] = '';
        self::assertFalse(buycoursesAdminTokenIsValid());
    }

    public function testTokenQueryCarriesTheCurrentToken(): void
    {
        parse_str(buycoursesAdminTokenQuery(), $parsed);
        self::assertSame([buycoursesAdminTokenName() => buycoursesAdminToken()], $parsed);
    }

    public function testSalesReportGridLinksCarryTheToken(): void
    {
        $sale = [
            'id' => 4242,
            'status' => BuyCoursesPlugin::SALE_STATUS_PENDING,
            'invoice' => 0,
            'complete_user_name' => 'Bp Buyer',
            'firstname' => 'Bp',
            'lastname' => 'Buyer',
            'date' => '2026-01-15 10:00:00',
            'reference' => 'bp_ref',
            'payment_type' => 'Transfer',
            'total_price' => '10',
            'product_type' => 'Course',
            'product_name' => 'bp_product',
            'email' => 'bp@example.com',
            'num_invoice' => '',
        ];
        $html = BuyCoursesPlugin::getSalesReportScript([$sale], false);

        // The row is JSON-encoded with JSON_HEX_AMP: "&" appears as \u0026.
        $token = buycoursesAdminToken();
        $tail = '\\u0026'.buycoursesAdminTokenName().'='.$token;
        self::assertStringContainsString('order=4242\\u0026action=confirm'.$tail, $html);
        self::assertStringContainsString('order=4242\\u0026action=cancel'.$tail, $html);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function saleReportPages(): array
    {
        return [
            'sales_report' => ['sales_report.php', 'completeSale'],
            'subscription_sales_report' => ['subscription_sales_report.php', 'completeSubscriptionSale'],
        ];
    }

    /**
     * @dataProvider saleReportPages
     */
    public function testSaleReportPagesCheckTheTokenBeforeTouchingASale(string $page, string $stateChange): void
    {
        $source = (string) file_get_contents(self::SRC.$page);

        $guard = strpos($source, 'buycoursesRequireAdminToken(');
        $firstRead = strpos($source, '$plugin->getSale');
        $firstRead = false === $firstRead ? (int) strpos($source, '$plugin->getSubscriptionSale') : $firstRead;
        $change = strpos($source, '$plugin->'.$stateChange.'(');

        self::assertNotFalse($guard, $page.' must call buycoursesRequireAdminToken()');
        self::assertNotFalse($change);
        self::assertLessThan($firstRead, $guard, 'the token is checked before the sale is even loaded');
        self::assertLessThan($change, $guard);
        self::assertStringContainsString("require_once __DIR__.'/admin_token.lib.php'", $source);
        self::assertStringContainsString('api_protect_admin_script()', $source);
    }

    public function testSubscriptionReportTemplateLinksCarryTheToken(): void
    {
        $tpl = (string) file_get_contents(self::SRC.'../view/subscription_sales_report.tpl');

        self::assertSame(2, substr_count($tpl, "'buycourses_admin_sec_token': admin_token"));
        self::assertStringContainsString(
            "buycoursesAdminToken()",
            (string) file_get_contents(self::SRC.'subscription_sales_report.php')
        );
    }
}
