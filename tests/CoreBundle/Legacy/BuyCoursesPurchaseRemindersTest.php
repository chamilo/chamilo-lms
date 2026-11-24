<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Helpers\BuyCoursesPurchaseReminderHelper;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Purchase-attempt reminders of the BuyCourses plugin (issue #2627).
 */
final class BuyCoursesPurchaseRemindersTest extends BuyCoursesCharacterizationTestCase
{
    private BuyCoursesPurchaseReminderHelper $reminders;

    /**
     * @var list<array{name: string, email: string, subject: string, body: string}>
     */
    private array $mails = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->selectCurrency('EUR');
        $this->setPluginSettings(['purchase_reminders_enable' => 'true', 'tax_enable' => 'false']);
        $this->reminders = new BuyCoursesPurchaseReminderHelper($this->conn);
        $this->reminders->setPlugin($this->plugin);
        $this->mails = [];
        $this->reminders->setMailSender(function (string $name, string $email, string $subject, string $body): bool {
            $this->mails[] = compact('name', 'email', 'subject', 'body');

            return true;
        });
        $this->loginAs(1);
    }

    public function testDayZeroWaitsOneHourThenBecomesDue(): void
    {
        $purchase = new DateTimeImmutable('2026-03-01 10:00:00', new DateTimeZone('UTC'));

        self::assertFalse(BuyCoursesPurchaseReminderHelper::isReminderDue(
            $purchase,
            0,
            $purchase->modify('+59 minutes')
        ));
        self::assertTrue(BuyCoursesPurchaseReminderHelper::isReminderDue(
            $purchase,
            0,
            $purchase->modify('+1 hour')
        ));
    }

    public function testDayNIsDueOnlyAfterAtLeastNDays(): void
    {
        $purchase = new DateTimeImmutable('2026-03-01 15:00:00', new DateTimeZone('UTC'));

        self::assertFalse(BuyCoursesPurchaseReminderHelper::isReminderDue(
            $purchase,
            5,
            $purchase->modify('+4 days 23 hours')
        ));
        self::assertTrue(BuyCoursesPurchaseReminderHelper::isReminderDue(
            $purchase,
            5,
            $purchase->modify('+5 days')
        ));
        self::assertTrue(BuyCoursesPurchaseReminderHelper::isReminderDue(
            $purchase,
            5,
            $purchase->modify('+6 days')
        ));
    }

    public function testSanitizeMailSubjectStripsTagsAndHeaderBreaks(): void
    {
        $subject = BuyCoursesPurchaseReminderHelper::sanitizeMailSubject(
            "Reminder\r\nBcc: evildoer@example.com\n<span lang=\"en\">Finish ((purchase_link))</span>"
        );

        self::assertStringNotContainsString("\n", $subject);
        self::assertStringNotContainsString("\r", $subject);
        self::assertStringNotContainsString('<span', $subject);
        self::assertSame('Reminder Bcc: evildoer@example.com Finish ((purchase_link))', $subject);
    }

    public function testPlaceholderIsReplacedInSubjectAndBody(): void
    {
        $url = 'https://example.org/plugin/BuyCourses/src/purchase_reminder_link.php?k=sale&s=9';
        $rendered = BuyCoursesPurchaseReminderHelper::renderPlaceholder(
            'Please finish at ((purchase_link)) or ignore.',
            $url
        );

        self::assertSame('Please finish at '.$url.' or ignore.', $rendered);
        self::assertStringNotContainsString('((purchase_link))', $rendered);
    }

    public function testDayZeroSaleIsSentOnceAndNotBeforeOneHour(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $this->makeItem(1, 80);
        $reminderId = $this->saveReminder(0, [1], []);
        $saleId = $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-01 10:00:00',
            'status' => BuyCoursesPlugin::SALE_STATUS_PENDING,
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-01 10:30:00', new DateTimeZone('UTC')));
        $result = $this->reminders->processReminders();
        self::assertSame(0, $result['sent']);
        self::assertSame([], $this->mails);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-01 11:05:00', new DateTimeZone('UTC')));
        $result = $this->reminders->processReminders();
        self::assertSame(1, $result['sent']);
        self::assertCount(1, $this->mails);
        self::assertSame('admin@example.org', $this->mails[0]['email']);
        self::assertStringContainsString('purchase_reminder_link.php', $this->mails[0]['body']);
        self::assertTrue($this->reminders->alreadySent(
            $reminderId,
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE,
            $saleId
        ));

        $this->mails = [];
        $result = $this->reminders->processReminders();
        self::assertSame(0, $result['sent']);
        self::assertSame([], $this->mails);
    }

    public function testDayNSaleAndIdempotentUniqueConstraint(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $this->makeItem(1, 80);
        $reminderId = $this->saveReminder(3, [1], []);
        $saleId = $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-01 09:00:00',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-04 08:59:00', new DateTimeZone('UTC')));
        self::assertSame(0, $this->reminders->processReminders()['sent']);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-04 09:00:00', new DateTimeZone('UTC')));
        self::assertSame(1, $this->reminders->processReminders()['sent']);
        self::assertTrue($this->reminders->alreadySent(
            $reminderId,
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE,
            $saleId
        ));
        self::assertSame(0, $this->reminders->processReminders()['sent']);
    }

    public function testStopsWhenSaleIsCompletedOrCancelledOrProductIsOffSale(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $this->makeItem(1, 80);
        $this->saveReminder(0, [1], []);
        $completed = $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-01 08:00:00',
            'status' => BuyCoursesPlugin::SALE_STATUS_COMPLETED,
            'reference' => 'bc_done',
        ]);
        $cancelled = $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-01 08:00:00',
            'status' => BuyCoursesPlugin::SALE_STATUS_CANCELED,
            'reference' => 'bc_cancel',
        ]);
        $offSale = $this->makeSale([
            'user_id' => 1,
            'product_id' => 2,
            'date' => '2026-03-01 08:00:00',
            'reference' => 'bc_off',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-02 08:00:00', new DateTimeZone('UTC')));
        self::assertSame(0, $this->reminders->processReminders()['sent']);
        self::assertFalse($this->reminders->alreadySent(1, BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE, $completed));
        self::assertSame([], $this->mails);

        $candidate = [
            'status' => BuyCoursesPlugin::SALE_STATUS_PENDING,
            'product_type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
            'product_id' => 2,
        ];
        self::assertTrue($this->reminders->mustStop($candidate));
        unset($completed, $cancelled, $offSale);
    }

    public function testStopsWhenSessionHasAlreadyStarted(): void
    {
        $sessionId = $this->ensureSession('2026-03-01 00:00:00');
        $this->enableFrom('2026-02-01 00:00:00');
        $this->makeItem($sessionId, 50, null, BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
        $this->saveReminder(0, [], [$sessionId]);
        $this->makeSale([
            'user_id' => 1,
            'product_type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
            'product_id' => $sessionId,
            'date' => '2026-02-20 10:00:00',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-02 12:00:00', new DateTimeZone('UTC')));
        self::assertTrue($this->reminders->sessionHasStarted($sessionId));
        self::assertSame(0, $this->reminders->processReminders()['sent']);
    }

    public function testPerProductScopeIgnoresUncheckedProducts(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $this->makeItem(1, 80);
        $this->makeItem(2, 90);
        $this->saveReminder(0, [1], []);
        $this->makeSale([
            'user_id' => 1,
            'product_id' => 2,
            'date' => '2026-03-01 08:00:00',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-02 08:00:00', new DateTimeZone('UTC')));
        self::assertSame(0, $this->reminders->processReminders()['sent']);
    }

    public function testActivationCutoffSkipsOlderSales(): void
    {
        $this->enableFrom('2026-03-10 12:00:00');
        $this->makeItem(1, 80);
        $this->saveReminder(0, [1], []);
        $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-10 11:59:00',
        ]);
        $included = $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-10 12:00:00',
            'reference' => 'bc_after',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-11 12:00:00', new DateTimeZone('UTC')));
        self::assertSame(1, $this->reminders->processReminders()['sent']);
        self::assertTrue($this->reminders->alreadySent(
            (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_reminder'),
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE,
            $included
        ));
    }

    public function testCouponIsSingleUseTiedToTheSaleAndExpiresWhenTheNextReminderIsSent(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $this->makeItem(1, 100);
        $first = $this->saveReminder(0, [1], [], [
            'discount_type' => BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE,
            'discount_amount' => 10,
            'discount_valid_days' => 365,
        ]);
        $second = $this->saveReminder(2, [1], [], [
            'discount_type' => BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE,
            'discount_amount' => 20,
            'discount_valid_days' => 365,
        ]);
        $saleId = $this->makeSale([
            'user_id' => 1,
            'product_id' => 1,
            'date' => '2026-03-01 08:00:00',
            'price' => '100.00',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-01 10:00:00', new DateTimeZone('UTC')));
        self::assertSame(1, $this->reminders->processReminders()['sent']);

        $firstCouponId = (int) $this->conn->fetchOne(
            'SELECT coupon_id FROM plugin_buycourses_reminder_sent WHERE reminder_id = ? AND sale_id = ?',
            [$first, $saleId]
        );
        $coupon = $this->conn->fetchAssociative(
            'SELECT * FROM plugin_buycourses_coupon WHERE id = ?',
            [$firstCouponId]
        );
        self::assertNotFalse($coupon);
        self::assertSame(1, (int) $coupon['times_applied']);
        self::assertSame(1, (int) $coupon['restricted_user_id']);
        self::assertSame(BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE, $coupon['restricted_sale_kind']);
        self::assertSame($saleId, (int) $coupon['restricted_sale_id']);
        self::assertSame('2027-03-01 10:00:00', (string) $coupon['valid_end']);

        $this->loginAs(2);
        self::assertSame(
            [],
            $this->plugin->getCoupon($firstCouponId, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, 1)
        );

        $this->loginAs(1);
        $found = $this->plugin->getCoupon($firstCouponId, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, 1);
        self::assertSame($firstCouponId, (int) $found['id']);

        self::assertTrue($this->reminders->applyCouponToPendingSale(
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE,
            $saleId,
            $firstCouponId,
            1
        ));
        self::assertFalse($this->reminders->applyCouponToPendingSale(
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SALE,
            $saleId,
            $firstCouponId,
            2
        ));
        $sale = $this->saleRow($saleId);
        self::assertEquals(90.0, (float) $sale['price']);
        self::assertEquals(10.0, (float) $sale['discount_amount']);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-03 08:00:00', new DateTimeZone('UTC')));
        self::assertSame(1, $this->reminders->processReminders()['sent']);
        $expired = $this->conn->fetchAssociative(
            'SELECT active, valid_end FROM plugin_buycourses_coupon WHERE id = ?',
            [$firstCouponId]
        );
        self::assertSame(0, (int) $expired['active']);
        self::assertSame('2026-03-03 08:00:00', (string) $expired['valid_end']);
        unset($second);
    }

    public function testServiceSaleIsRemindedAndResumeRequiresOwnership(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $serviceId = $this->makeService();
        $this->saveReminder(
            0,
            [],
            [],
            ['products' => [['product_type' => BuyCoursesPlugin::PRODUCT_TYPE_SERVICE, 'product_id' => $serviceId]]]
        );
        $saleId = $this->makeServiceSale($serviceId, [
            'buyer_id' => 1,
            'buy_date' => '2026-03-01 08:00:00',
        ]);

        $this->reminders->setNow(new DateTimeImmutable('2026-03-01 12:00:00', new DateTimeZone('UTC')));
        self::assertSame(1, $this->reminders->processReminders()['sent']);

        $forbidden = $this->reminders->resumePurchaseForUser(
            2,
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SERVICE,
            $saleId
        );
        self::assertFalse($forbidden['ok']);
        self::assertSame('forbidden', $forbidden['error']);

        $ok = $this->reminders->resumePurchaseForUser(
            1,
            BuyCoursesPurchaseReminderHelper::SALE_KIND_SERVICE,
            $saleId
        );
        self::assertTrue($ok['ok']);
        self::assertStringContainsString('service_process_confirm.php', (string) $ok['redirect']);
    }

    public function testAdminCrudValidatesAndDeletes(): void
    {
        $this->makeItem(1, 80);
        $id = $this->reminders->saveReminder([
            'days_after' => 1,
            'subject' => 'Subject ((purchase_link))',
            'body' => '<p>Hello ((purchase_link))</p>',
            'active' => 1,
            'products' => [['product_type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE, 'product_id' => 1]],
        ]);
        self::assertGreaterThan(0, $id);
        $row = $this->reminders->getReminder($id);
        self::assertSame(1, (int) $row['days_after']);
        self::assertCount(1, $row['products']);

        $this->reminders->saveReminder([
            'days_after' => 4,
            'subject' => 'Updated',
            'body' => '<p>Updated body</p>',
            'active' => 0,
            'products' => [['product_type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE, 'product_id' => 1]],
        ], $id);
        self::assertSame(4, (int) $this->reminders->getReminder($id)['days_after']);
        self::assertSame(0, (int) $this->reminders->getReminder($id)['active']);

        try {
            $this->reminders->saveReminder([
                'days_after' => 1,
                'subject' => 'No products',
                'body' => '<p>No products</p>',
                'products' => [],
            ]);
            self::fail('Expected invalid products');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('products', $exception->getMessage());
        }

        self::assertTrue($this->reminders->deleteReminder($id));
        self::assertNull($this->reminders->getReminder($id));
    }

    public function testCronIsStaleUntilTheCommandCompletes(): void
    {
        $this->enableFrom('2026-03-01 00:00:00');
        $this->reminders->setNow(new DateTimeImmutable('2026-03-02 12:00:00', new DateTimeZone('UTC')));
        self::assertTrue($this->reminders->cronIsStale());

        $this->reminders->processReminders();
        self::assertFalse($this->reminders->cronIsStale());

        $this->reminders->setNow(new DateTimeImmutable('2026-03-03 12:01:00', new DateTimeZone('UTC')));
        self::assertTrue($this->reminders->cronIsStale());
    }

    public function testAdminPagesRequireAdminScriptAndPostToken(): void
    {
        $src = \dirname(__DIR__, 3).'/public/plugin/BuyCourses/src/';
        foreach (['reminders.php', 'reminder_add.php'] as $page) {
            $code = (string) file_get_contents($src.$page);
            self::assertStringContainsString('api_protect_admin_script()', $code, $page);
            self::assertStringContainsString('admin_token.lib.php', $code, $page);
            self::assertStringContainsString('buycoursesRequireAdminToken', $code, $page);
        }

        $list = (string) file_get_contents($src.'reminders.php');
        self::assertStringNotContainsString("\$_GET['action']", $list);
        self::assertStringContainsString("'POST'", $list);
        self::assertStringContainsString('delete', $list);

        $link = (string) file_get_contents($src.'purchase_reminder_link.php');
        self::assertStringContainsString('resumePurchaseForUser', $link);
        self::assertStringContainsString('api_get_user_id()', $link);
    }

    public function testCommandRejectsAnOutOfRangeLimit(): void
    {
        $src = (string) file_get_contents(
            \dirname(__DIR__, 3).'/src/CoreBundle/Command/BuyCoursesSendPurchaseRemindersCommand.php'
        );
        self::assertStringContainsString('buycourses:send-purchase-reminders', $src);
        self::assertStringContainsString('MAX_BATCH_SIZE', $src);
        self::assertStringContainsString('--limit', $src);
    }

    private function enableFrom(string $at): void
    {
        $this->plugin->getGlobalParameters();
        $this->conn->executeStatement(
            'UPDATE plugin_buycourses_global_config SET purchase_reminders_enabled_at = ?, purchase_reminders_last_run_at = NULL',
            [$at]
        );
    }

    /**
     * @param list<int>            $courseIds
     * @param list<int>            $sessionIds
     * @param array<string, mixed> $extra
     */
    private function saveReminder(int $daysAfter, array $courseIds, array $sessionIds, array $extra = []): int
    {
        $products = $extra['products'] ?? [];
        foreach ($courseIds as $courseId) {
            $products[] = [
                'product_type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
                'product_id' => $courseId,
            ];
        }
        foreach ($sessionIds as $sessionId) {
            $products[] = [
                'product_type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
                'product_id' => $sessionId,
            ];
        }

        return $this->reminders->saveReminder(array_merge([
            'days_after' => $daysAfter,
            'subject' => 'Please finish ((purchase_link))',
            'body' => '<p>Complete your purchase: ((purchase_link))</p>',
            'active' => 1,
            'products' => $products,
        ], $extra));
    }

    private function makeService(): int
    {
        $this->conn->insert('plugin_buycourses_services', [
            'name' => 'bc_reminder_service',
            'description' => 'bc_desc',
            'price' => '40.00',
            'duration_days' => 30,
            'applies_to' => 1,
            'owner_id' => 1,
            'active' => 1,
            'visibility' => 1,
            'video_url' => '',
            'image' => '',
            'service_information' => '',
        ]);

        return (int) $this->conn->lastInsertId();
    }

    /**
     * @param array<string, mixed> $override
     */
    private function makeServiceSale(int $serviceId, array $override = []): int
    {
        $this->conn->insert('plugin_buycourses_service_sale', array_merge([
            'service_id' => $serviceId,
            'reference' => 'bc_sref_'.uniqid('', true),
            'currency_id' => (int) $this->conn->fetchOne('SELECT id FROM plugin_buycourses_currency WHERE status = 1'),
            'price' => '40.00',
            'node_type' => 1,
            'node_id' => 1,
            'buyer_id' => 1,
            'buy_date' => '2026-03-01 08:00:00',
            'date_start' => '2026-03-01 08:00:00',
            'date_end' => '2026-03-31 08:00:00',
            'status' => 0,
            'payment_type' => 2,
            'invoice' => 0,
        ], $override));

        return (int) $this->conn->lastInsertId();
    }

    private function ensureSession(string $accessStart): int
    {
        $id = (int) $this->conn->fetchOne('SELECT MIN(id) FROM session');
        if ($id > 0) {
            $this->conn->executeStatement(
                'UPDATE session SET access_start_date = ? WHERE id = ?',
                [$accessStart, $id]
            );

            return $id;
        }

        $this->conn->insert('session', [
            'title' => 'Reminder session',
            'access_start_date' => $accessStart,
            'nbr_courses' => 0,
            'nbr_users' => 0,
            'nbr_classes' => 0,
            'visibility' => 1,
            'status' => 0,
        ]);

        return (int) $this->conn->lastInsertId();
    }
}
