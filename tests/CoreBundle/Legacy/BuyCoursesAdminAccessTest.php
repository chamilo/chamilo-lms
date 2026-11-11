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
        $tail = '\u0026'.buycoursesAdminTokenName().'='.$token;
        self::assertStringContainsString('order=4242\u0026action=confirm'.$tail, $html);
        self::assertStringContainsString('order=4242\u0026action=cancel'.$tail, $html);
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

    public function testSubscriptionReportTemplateLinksCarryTheToken(): void
    {
        $tpl = (string) file_get_contents(self::SRC.'../view/subscription_sales_report.tpl');

        self::assertSame(2, substr_count($tpl, "'buycourses_admin_sec_token': admin_token"));
        self::assertStringContainsString(
            'buycoursesAdminToken()',
            (string) file_get_contents(self::SRC.'subscription_sales_report.php')
        );
    }

    /**
     * @dataProvider adminFormPages
     */
    public function testAdminFormPagesRefuseAPostWithoutTokenBeforeBuildingAnyForm(string $page): void
    {
        $source = (string) file_get_contents(self::SRC.$page);

        $admin = strpos($source, 'api_protect_admin_script(');
        $guard = strpos($source, 'buycoursesRequireAdminTokenOnPost(');
        $firstForm = strpos($source, 'new FormValidator(');

        self::assertNotFalse($admin);
        self::assertNotFalse($guard, $page.' must refuse a POST without token');
        self::assertLessThan($guard, $admin, 'the role check comes first');
        self::assertLessThan($firstForm, $guard, 'the token is checked before any form can validate');
        self::assertGreaterThanOrEqual(
            substr_count($source, 'new FormValidator('),
            substr_count($source, 'buycoursesAdminTokenField('),
            'every form of the page carries the hidden token'
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminFormPages(): array
    {
        return [
            'paymentsetup' => ['paymentsetup.php'],
            'services_add' => ['services_add.php'],
            'services_edit' => ['services_edit.php'],
            'subscription_add' => ['subscription_add.php'],
            'configure_course' => ['configure_course.php'],
            'configure_subscription' => ['configure_subscription.php'],
            'configure_frequency' => ['configure_frequency.php'],
        ];
    }

    public function testDeleteTransferAccountLinkIsNotAnUnsignedGet(): void
    {
        $source = (string) file_get_contents(self::SRC.'paymentsetup.php');
        $delete = (int) strpos($source, "'delete_taccount' === \$_GET['action']");
        $guard = (int) strpos($source, 'buycoursesRequireAdminToken(api_get_self())');
        $call = (int) strpos($source, '->deleteTransferAccount(');

        self::assertGreaterThan($delete, $guard);
        self::assertLessThan($call, $guard);
        self::assertStringContainsString(
            "'buycourses_admin_sec_token': admin_token",
            (string) file_get_contents(self::SRC.'../view/paymentsetup.tpl')
        );
    }

    public function testDeleteFrequencyFormsPostTheToken(): void
    {
        foreach (['configure_frequency.tpl', 'configure_subscription.tpl'] as $tpl) {
            self::assertStringContainsString(
                'name="buycourses_admin_sec_token" value="{{ admin_token',
                (string) file_get_contents(self::SRC.'../view/'.$tpl)
            );
        }
    }

    public function testAjaxStateChangingAdminActionsNeedTheToken(): void
    {
        $source = (string) file_get_contents(self::SRC.'buycourses.ajax.php');

        $guard = (int) strpos($source, 'buycoursesAdminTokenIsValid()');
        $switch = (int) strpos($source, 'switch ($action)');
        self::assertGreaterThan(0, $guard);
        self::assertLessThan($switch, $guard);
        $guardBlock = substr($source, (int) strpos($source, 'Admin actions that change something'), 400);
        foreach (['proceedPayout', 'cancelPayout', 'service_sale_confirm', 'service_sale_cancel'] as $action) {
            self::assertStringContainsString("'".$action."'", $guardBlock);
        }

        // The pages that call these actions send the token.
        self::assertStringContainsString('buycourses_admin_sec_token', (string) file_get_contents(self::SRC.'../view/service_sales_report.tpl'));
        self::assertSame(2, substr_count((string) file_get_contents(self::SRC.'../view/paypal_payout.tpl'), 'buycourses_admin_sec_token'));
    }

    public function testOnlyTheBuyerOwnsAServiceSale(): void
    {
        $this->conn->insert('plugin_buycourses_services', [
            'name' => 'bp_service', 'description' => 'bp', 'price' => '100.00', 'duration_days' => 30,
            'applies_to' => 1, 'owner_id' => 1, 'active' => 1, 'visibility' => 1, 'video_url' => '',
            'image' => '', 'service_information' => '',
        ]);
        $serviceId = (int) $this->conn->lastInsertId();
        $this->conn->insert('plugin_buycourses_service_sale', [
            'service_id' => $serviceId, 'reference' => 'bp_sref_'.uniqid('', true),
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'price' => '100.00', 'node_type' => 1, 'node_id' => 2, 'buyer_id' => 2,
            'buy_date' => '2026-01-10 08:00:00', 'date_start' => '2026-01-10 08:00:00',
            'date_end' => '2026-02-09 08:00:00', 'status' => 0, 'payment_type' => 2, 'invoice' => 0,
        ]);
        $sale = $this->plugin->getServiceSale((int) $this->conn->lastInsertId());

        self::assertTrue(buycoursesUserOwnsServiceSale($sale, 2));
        self::assertFalse(buycoursesUserOwnsServiceSale($sale, 1), 'another user (even an admin) is not the buyer');
        self::assertFalse(buycoursesUserOwnsServiceSale($sale, 0), 'nobody is not the buyer');
        self::assertFalse(buycoursesUserOwnsServiceSale([], 2), 'unknown sale');
        self::assertFalse(buycoursesUserOwnsServiceSale([], 0), 'unknown sale and nobody');
    }

    public function testStripeServiceCancelPageChecksTheBuyerBeforeCancelling(): void
    {
        $source = (string) file_get_contents(self::SRC.'service_stripe_cancel.php');

        $guard = (int) strpos($source, 'buycoursesUserOwnsServiceSale(');
        $cancel = (int) strpos($source, '->cancelServiceSale(');
        self::assertGreaterThan(0, $guard);
        self::assertLessThan($cancel, $guard);
    }

    public function testOnlyTheBuyerOwnsASale(): void
    {
        $saleId = $this->makeSale(['user_id' => 2]);
        $sale = $this->plugin->getSale($saleId);

        self::assertTrue(buycoursesUserOwnsSale($sale, 2));
        self::assertFalse(buycoursesUserOwnsSale($sale, 1), 'another user (even an admin) is not the buyer');
        self::assertFalse(buycoursesUserOwnsSale($sale, 0), 'nobody is not the buyer');
        self::assertFalse(buycoursesUserOwnsSale([], 2), 'unknown sale');
        self::assertFalse(buycoursesUserOwnsSale([], 0), 'unknown sale and nobody');
    }

    /**
     * @dataProvider successPages
     */
    public function testSuccessPagesCheckTheBuyerBeforeShowingOrCompletingTheSale(string $page, string $firstUse): void
    {
        $source = (string) file_get_contents(self::SRC.$page);

        $guard = strpos($source, 'buycoursesUserOwns');
        $use = strpos($source, $firstUse);

        self::assertNotFalse($guard, $page.' must check that the sale belongs to the current user');
        self::assertNotFalse($use);
        self::assertLessThan($use, $guard);
        self::assertStringContainsString("require_once __DIR__.'/admin_token.lib.php'", $source);
    }

    public static function successPages(): iterable
    {
        yield ['success.php', '$buyingCourse = false;'];
        yield ['subscription_success.php', '$coupon = [];'];
        yield ['service_success.php', '$couponId = isset('];
        yield ['stripe_success.php', '$userInfo = api_get_user_info('];
    }
}
