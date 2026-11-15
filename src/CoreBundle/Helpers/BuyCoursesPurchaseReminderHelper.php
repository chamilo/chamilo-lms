<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use BuyCoursesPlugin;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use InvalidArgumentException;
use Security;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Purchase-attempt reminders for the BuyCourses plugin (courses, sessions and services).
 *
 * Reminders fire a set number of days after a pending sale, not before a session start date.
 */
final class BuyCoursesPurchaseReminderHelper
{
    public const string SALE_KIND_SALE = 'sale';
    public const string SALE_KIND_SERVICE = 'service_sale';
    public const string PLACEHOLDER = '((purchase_link))';
    public const int DEFAULT_BATCH_SIZE = 100;
    public const int MAX_BATCH_SIZE = 500;
    public const int DAY_ZERO_MIN_AGE_SECONDS = 3600;
    public const int DEFAULT_COUPON_VALID_DAYS = 30;
    public const int CRON_STALE_AFTER_SECONDS = 86400;

    private const string TABLE_REMINDER = 'plugin_buycourses_reminder';
    private const string TABLE_REMINDER_ITEM = 'plugin_buycourses_reminder_rel_item';
    private const string TABLE_REMINDER_SENT = 'plugin_buycourses_reminder_sent';
    private const string TABLE_SALE = 'plugin_buycourses_sale';
    private const string TABLE_SERVICE_SALE = 'plugin_buycourses_service_sale';
    private const string TABLE_ITEM = 'plugin_buycourses_item';
    private const string TABLE_SERVICES = 'plugin_buycourses_services';
    private const string TABLE_COUPON = 'plugin_buycourses_coupon';
    private const string TABLE_COUPON_ITEM = 'plugin_buycourses_coupon_rel_item';
    private const string TABLE_COUPON_SERVICE = 'plugin_buycourses_coupon_rel_service';
    private const string TABLE_COUPON_SALE = 'plugin_buycourses_coupon_rel_sale';
    private const string TABLE_COUPON_SERVICE_SALE = 'plugin_buycourses_coupon_rel_service_sale';
    private const string TABLE_GLOBAL_CONFIG = 'plugin_buycourses_global_config';
    private const string TABLE_SESSION = 'session';
    private const string TABLE_COURSE = 'course';
    private const string TABLE_USER = 'user';

    /**
     * @var callable(string, string, string, string): bool|null
     */
    private $mailSender;

    private ?DateTimeImmutable $nowOverride = null;

    private ?BuyCoursesPlugin $pluginInstance = null;

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function setMailSender(?callable $mailSender): void
    {
        $this->mailSender = $mailSender;
    }

    public function setNow(?DateTimeImmutable $now): void
    {
        $this->nowOverride = $now;
    }

    public function setPlugin(?BuyCoursesPlugin $plugin): void
    {
        $this->pluginInstance = $plugin;
    }

    public function now(): DateTimeImmutable
    {
        return $this->nowOverride ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    /**
     * Whether a reminder configured for $daysAfter should be sent for a purchase made at $purchaseAt.
     *
     * Day 0 is due on the first cron run after the attempt, but not before one hour has passed.
     * Day N (N >= 1) is due once at least N days have elapsed.
     */
    public static function isReminderDue(
        DateTimeInterface $purchaseAt,
        int $daysAfter,
        DateTimeInterface $now,
    ): bool {
        if ($daysAfter < 0) {
            return false;
        }

        $purchase = DateTimeImmutable::createFromInterface($purchaseAt);
        $current = DateTimeImmutable::createFromInterface($now);

        if (0 === $daysAfter) {
            return $current->getTimestamp() >= $purchase->getTimestamp() + self::DAY_ZERO_MIN_AGE_SECONDS;
        }

        return $current >= $purchase->modify('+'.$daysAfter.' days');
    }

    /**
     * Strip tags and line breaks so a translate_html subject cannot inject mail headers.
     */
    public static function sanitizeMailSubject(string $subject): string
    {
        $subject = html_entity_decode(strip_tags($subject), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $subject = str_replace(["\r", "\n", "\0"], ' ', $subject);

        return trim((string) preg_replace('/\s+/u', ' ', $subject));
    }

    public static function renderPlaceholder(string $text, string $url): string
    {
        return str_replace(self::PLACEHOLDER, $url, $text);
    }

    public function isFeatureEnabled(?BuyCoursesPlugin $plugin = null): bool
    {
        $plugin ??= $this->plugin();
        $value = $plugin->get('purchase_reminders_enable');

        return true === $value || 'true' === $value;
    }

    /**
     * Warn when the feature is on but the console command has not completed in the last 24 hours.
     */
    public function cronIsStale(?DateTimeImmutable $now = null): bool
    {
        if (!$this->isFeatureEnabled() || !$this->hasReminderInfrastructure()) {
            return false;
        }

        $lastRun = $this->getLastRunAt();
        if (null === $lastRun) {
            return true;
        }

        $now ??= $this->now();

        return ($now->getTimestamp() - $lastRun->getTimestamp()) >= self::CRON_STALE_AFTER_SECONDS;
    }

    public function getLastRunAt(): ?DateTimeImmutable
    {
        if (!$this->hasGlobalConfigColumn('purchase_reminders_last_run_at')) {
            return null;
        }

        $value = $this->connection->fetchOne(
            'SELECT purchase_reminders_last_run_at FROM '.self::TABLE_GLOBAL_CONFIG.' ORDER BY id ASC LIMIT 1'
        );

        return $this->parseDate($value);
    }

    public function getEnabledAt(): ?DateTimeImmutable
    {
        if (!$this->hasGlobalConfigColumn('purchase_reminders_enabled_at')) {
            return null;
        }

        $value = $this->connection->fetchOne(
            'SELECT purchase_reminders_enabled_at FROM '.self::TABLE_GLOBAL_CONFIG.' ORDER BY id ASC LIMIT 1'
        );

        return $this->parseDate($value);
    }

    /**
     * Record the first time the feature is switched on. Later toggles keep the original cutoff.
     */
    public function rememberActivationTimestamp(?DateTimeImmutable $now = null): void
    {
        if (!$this->hasGlobalConfigColumn('purchase_reminders_enabled_at')) {
            return;
        }

        $now ??= $this->now();
        $existing = $this->getEnabledAt();
        if (null !== $existing) {
            return;
        }

        $this->ensureGlobalConfigRow();
        $this->connection->executeStatement(
            'UPDATE '.self::TABLE_GLOBAL_CONFIG.' SET purchase_reminders_enabled_at = ? WHERE purchase_reminders_enabled_at IS NULL',
            [$now->format('Y-m-d H:i:s')]
        );
    }

    public function markLastRun(?DateTimeImmutable $now = null): void
    {
        if (!$this->hasGlobalConfigColumn('purchase_reminders_last_run_at')) {
            return;
        }

        $now ??= $this->now();
        $this->ensureGlobalConfigRow();
        $this->connection->executeStatement(
            'UPDATE '.self::TABLE_GLOBAL_CONFIG.' SET purchase_reminders_last_run_at = ?',
            [$now->format('Y-m-d H:i:s')]
        );
    }

    /**
     * @return array{sent: int, skipped: int, dry_run: bool}
     */
    public function processReminders(int $limit = self::DEFAULT_BATCH_SIZE, bool $dryRun = false): array
    {
        $limit = max(1, min(self::MAX_BATCH_SIZE, $limit));
        $sent = 0;
        $skipped = 0;

        if (!$this->isFeatureEnabled() || !$this->hasReminderInfrastructure()) {
            return ['sent' => 0, 'skipped' => 0, 'dry_run' => $dryRun];
        }

        $this->rememberActivationTimestamp();
        $enabledAt = $this->getEnabledAt();
        if (null === $enabledAt) {
            return ['sent' => 0, 'skipped' => 0, 'dry_run' => $dryRun];
        }

        $now = $this->now();
        $candidates = $this->findDueCandidates($enabledAt, $now, $limit * 20);

        foreach ($candidates as $candidate) {
            if ($sent >= $limit) {
                break;
            }

            if (!$this->shouldSend($candidate, $now)) {
                ++$skipped;

                continue;
            }

            if ($dryRun) {
                ++$sent;

                continue;
            }

            if ($this->sendCandidate($candidate, $now)) {
                ++$sent;
            } else {
                ++$skipped;
            }
        }

        if (!$dryRun) {
            $this->markLastRun($now);
        }

        return ['sent' => $sent, 'skipped' => $skipped, 'dry_run' => $dryRun];
    }

    /**
     * @param array{
     *     days_after?: int,
     *     subject?: string,
     *     body?: string,
     *     discount_type?: int|null,
     *     discount_amount?: float|string|null,
     *     discount_valid_days?: int|null,
     *     active?: int|bool,
     *     products?: list<array{product_type: int, product_id: int}>
     * } $data
     */
    public function saveReminder(array $data, ?int $reminderId = null): int
    {
        $daysAfter = (int) ($data['days_after'] ?? -1);
        if ($daysAfter < 0 || $daysAfter > 3650) {
            throw new InvalidArgumentException('days_after');
        }

        $subject = Security::remove_XSS((string) ($data['subject'] ?? ''));
        $body = Security::remove_XSS((string) ($data['body'] ?? ''));
        if ('' === trim(strip_tags($subject)) || '' === trim(strip_tags($body))) {
            throw new InvalidArgumentException('content');
        }

        $discountType = isset($data['discount_type']) ? (int) $data['discount_type'] : 0;
        $discountAmount = isset($data['discount_amount']) ? (float) $data['discount_amount'] : 0.0;
        $discountValidDays = isset($data['discount_valid_days']) && '' !== (string) $data['discount_valid_days']
            ? (int) $data['discount_valid_days']
            : null;

        if ($discountType > 0) {
            if (
                !\in_array(
                    $discountType,
                    [BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE, BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_AMOUNT],
                    true
                )
            ) {
                throw new InvalidArgumentException('discount_type');
            }
            if ($discountAmount <= 0) {
                throw new InvalidArgumentException('discount_amount');
            }
            if (
                BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE === $discountType
                && $discountAmount > 100
            ) {
                throw new InvalidArgumentException('discount_amount');
            }
        } else {
            $discountType = null;
            $discountAmount = null;
        }

        if (null !== $discountValidDays && $discountValidDays < 0) {
            throw new InvalidArgumentException('discount_valid_days');
        }

        $products = $this->normalizeProducts($data['products'] ?? []);
        if ([] === $products) {
            throw new InvalidArgumentException('products');
        }

        $now = $this->now()->format('Y-m-d H:i:s');
        $values = [
            'days_after' => $daysAfter,
            'subject' => $subject,
            'body' => $body,
            'discount_type' => $discountType,
            'discount_amount' => $discountAmount,
            'discount_valid_days' => $discountValidDays,
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => $now,
        ];

        $this->connection->beginTransaction();

        try {
            if (null !== $reminderId && $reminderId > 0) {
                $existing = $this->getReminder($reminderId);
                if (null === $existing) {
                    throw new InvalidArgumentException('reminder');
                }
                $this->connection->update(self::TABLE_REMINDER, $values, ['id' => $reminderId]);
                $this->connection->delete(self::TABLE_REMINDER_ITEM, ['reminder_id' => $reminderId]);
            } else {
                $values['created_at'] = $now;
                $this->connection->insert(self::TABLE_REMINDER, $values);
                $reminderId = (int) $this->connection->lastInsertId();
            }

            foreach ($products as $product) {
                $this->connection->insert(self::TABLE_REMINDER_ITEM, [
                    'reminder_id' => $reminderId,
                    'product_type' => $product['product_type'],
                    'product_id' => $product['product_id'],
                ]);
            }

            $this->connection->commit();
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        return (int) $reminderId;
    }

    public function deleteReminder(int $reminderId): bool
    {
        if ($reminderId <= 0 || null === $this->getReminder($reminderId)) {
            return false;
        }

        $this->connection->delete(self::TABLE_REMINDER_ITEM, ['reminder_id' => $reminderId]);
        $this->connection->delete(self::TABLE_REMINDER, ['id' => $reminderId]);

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getReminder(int $reminderId): ?array
    {
        if ($reminderId <= 0 || !$this->hasReminderInfrastructure()) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT * FROM '.self::TABLE_REMINDER.' WHERE id = ?',
            [$reminderId]
        );
        if (!\is_array($row)) {
            return null;
        }

        $row['products'] = $this->getReminderProducts($reminderId);

        return $row;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listReminders(): array
    {
        if (!$this->hasReminderInfrastructure()) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM '.self::TABLE_REMINDER.' ORDER BY days_after ASC, id ASC'
        );

        foreach ($rows as &$row) {
            $row['products'] = $this->getReminderProducts((int) $row['id']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array{
     *     courses: list<array{id: int, title: string}>,
     *     sessions: list<array{id: int, title: string}>,
     *     services: list<array{id: int, title: string}>
     * }
     */
    public function getProductsOnSale(): array
    {
        $courses = $this->connection->fetchAllAssociative(
            'SELECT i.product_id AS id, c.title AS title
             FROM '.self::TABLE_ITEM.' i
             INNER JOIN '.self::TABLE_COURSE.' c ON c.id = i.product_id
             WHERE i.product_type = ?
             ORDER BY c.title ASC',
            [BuyCoursesPlugin::PRODUCT_TYPE_COURSE]
        );
        $sessions = $this->connection->fetchAllAssociative(
            'SELECT i.product_id AS id, s.title AS title
             FROM '.self::TABLE_ITEM.' i
             INNER JOIN '.self::TABLE_SESSION.' s ON s.id = i.product_id
             WHERE i.product_type = ?
             ORDER BY s.title ASC',
            [BuyCoursesPlugin::PRODUCT_TYPE_SESSION]
        );
        $services = $this->connection->fetchAllAssociative(
            'SELECT id, name AS title
             FROM '.self::TABLE_SERVICES.'
             WHERE active = 1
             ORDER BY name ASC'
        );

        $map = static function (array $rows): array {
            $items = [];
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                $title = trim((string) ($row['title'] ?? ''));
                if ($id > 0 && '' !== $title) {
                    $items[] = ['id' => $id, 'title' => $title];
                }
            }

            return $items;
        };

        return [
            'courses' => $map($courses),
            'sessions' => $map($sessions),
            'services' => $map($services),
        ];
    }

    public function buildPurchaseLink(string $saleKind, int $saleId, ?int $couponId = null): string
    {
        $params = [
            'k' => $saleKind,
            's' => $saleId,
        ];
        if (null !== $couponId && $couponId > 0) {
            $params['c'] = $couponId;
        }

        $base = \function_exists('api_get_path')
            ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/purchase_reminder_link.php'
            : '/plugin/BuyCourses/src/purchase_reminder_link.php';

        return $base.'?'.http_build_query($params);
    }

    /**
     * Resume a pending sale for the logged-in buyer and optionally apply the reminder coupon.
     *
     * @return array{ok: bool, redirect: ?string, error: ?string, coupon_id: ?int}
     */
    public function resumePurchaseForUser(int $userId, string $saleKind, int $saleId, ?int $couponId = null): array
    {
        $fail = static fn (string $error): array => [
            'ok' => false,
            'redirect' => null,
            'error' => $error,
            'coupon_id' => null,
        ];

        if ($userId <= 0 || $saleId <= 0) {
            return $fail('forbidden');
        }

        if (!\in_array($saleKind, [self::SALE_KIND_SALE, self::SALE_KIND_SERVICE], true)) {
            return $fail('forbidden');
        }

        $sale = $this->loadSale($saleKind, $saleId);
        if (null === $sale) {
            return $fail('not_found');
        }

        if ((int) $sale['user_id'] !== $userId) {
            return $fail('forbidden');
        }

        if (BuyCoursesPlugin::SALE_STATUS_PENDING !== (int) $sale['status']) {
            return $fail('not_pending');
        }

        $appliedCouponId = null;
        $couponError = false;
        if (null !== $couponId && $couponId > 0) {
            if ($this->applyCouponToPendingSale($saleKind, $saleId, $couponId, $userId)) {
                $appliedCouponId = $couponId;
            } else {
                $couponError = true;
            }
        }

        $redirect = self::SALE_KIND_SERVICE === $saleKind
            ? 'service_process_confirm.php'
            : 'process_confirm.php';

        $base = \function_exists('api_get_path')
            ? api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/'.$redirect
            : '/plugin/BuyCourses/src/'.$redirect;

        return [
            'ok' => true,
            'redirect' => $base,
            'error' => $couponError ? 'coupon' : null,
            'coupon_id' => $appliedCouponId,
        ];
    }

    /**
     * Apply a reminder coupon to a pending sale owned by $userId.
     */
    public function applyCouponToPendingSale(string $saleKind, int $saleId, int $couponId, int $userId): bool
    {
        $sale = $this->loadSale($saleKind, $saleId);
        $coupon = $this->connection->fetchAssociative(
            'SELECT * FROM '.self::TABLE_COUPON.' WHERE id = ?',
            [$couponId]
        );

        if (!\is_array($sale) || !\is_array($coupon)) {
            return false;
        }
        if ((int) $sale['user_id'] !== $userId) {
            return false;
        }
        if (BuyCoursesPlugin::SALE_STATUS_PENDING !== (int) $sale['status']) {
            return false;
        }

        $restrictedUser = (int) ($coupon['restricted_user_id'] ?? 0);
        $restrictedSaleId = (int) ($coupon['restricted_sale_id'] ?? 0);
        $restrictedKind = (string) ($coupon['restricted_sale_kind'] ?? '');
        if ($restrictedUser !== $userId || $restrictedSaleId !== $saleId || $restrictedKind !== $saleKind) {
            return false;
        }
        if (1 !== (int) ($coupon['active'] ?? 0)) {
            return false;
        }

        $now = $this->now()->format('Y-m-d H:i:s');
        if (($coupon['valid_start'] ?? '') > $now || ($coupon['valid_end'] ?? '') < $now) {
            return false;
        }

        $plugin = $this->plugin();
        $productType = (int) $sale['product_type'];
        $productId = (int) $sale['product_id'];
        $itemType = self::SALE_KIND_SERVICE === $saleKind
            ? BuyCoursesPlugin::PRODUCT_TYPE_SERVICE
            : $productType;
        // Look up the catalogue price without the coupon: getItemByProduct() would
        // already subtract the discount, and applying it again would double it.
        $item = BuyCoursesPlugin::PRODUCT_TYPE_SERVICE === $itemType
            ? null
            : $plugin->getItemByProduct($productId, $itemType);

        $basePrice = (float) ($item['price'] ?? $sale['price_without_discount'] ?? $sale['price'] ?? 0);
        if (self::SALE_KIND_SERVICE === $saleKind) {
            $service = $this->connection->fetchAssociative(
                'SELECT price FROM '.self::TABLE_SERVICES.' WHERE id = ?',
                [$productId]
            );
            $basePrice = (float) ($service['price'] ?? $sale['price'] ?? 0);
        }

        $discount = $this->computeDiscount($coupon, $basePrice);
        $price = max(0, $basePrice - $discount);

        $fields = [
            'price' => $price,
            'price_without_discount' => $basePrice,
            'discount_amount' => $discount,
        ];

        if (self::SALE_KIND_SALE === $saleKind) {
            $this->connection->update(self::TABLE_SALE, $fields, ['id' => $saleId]);
            $this->ensureCouponSaleLink(self::TABLE_COUPON_SALE, 'sale_id', $couponId, $saleId);
        } else {
            $this->connection->update(self::TABLE_SERVICE_SALE, $fields, ['id' => $saleId]);
            $this->ensureCouponSaleLink(self::TABLE_COUPON_SERVICE_SALE, 'service_sale_id', $couponId, $saleId);
        }

        return true;
    }

    public function couponIsUsableByUser(array $coupon, int $userId): bool
    {
        $restrictedUserId = (int) ($coupon['restricted_user_id'] ?? 0);
        if ($restrictedUserId <= 0) {
            return true;
        }

        return $userId > 0 && $restrictedUserId === $userId;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function findDueCandidates(
        DateTimeImmutable $enabledAt,
        DateTimeImmutable $now,
        int $fetchLimit,
    ): array {
        $candidates = array_merge(
            $this->querySaleCandidates($enabledAt, $now, $fetchLimit, self::SALE_KIND_SALE),
            $this->querySaleCandidates($enabledAt, $now, $fetchLimit, self::SALE_KIND_SERVICE),
        );

        usort(
            $candidates,
            static function (array $left, array $right): int {
                $date = strcmp((string) $left['purchase_at'], (string) $right['purchase_at']);
                if (0 !== $date) {
                    return $date;
                }

                return ((int) $left['days_after']) <=> ((int) $right['days_after']);
            }
        );

        return $candidates;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function querySaleCandidates(
        DateTimeImmutable $enabledAt,
        DateTimeImmutable $now,
        int $fetchLimit,
        string $saleKind,
    ): array {
        if (self::SALE_KIND_SALE === $saleKind) {
            $sql = 'SELECT
                        s.id AS sale_id,
                        s.user_id,
                        s.product_type,
                        s.product_id,
                        s.product_name,
                        s.status,
                        s.date AS purchase_at,
                        r.id AS reminder_id,
                        r.days_after,
                        r.subject,
                        r.body,
                        r.discount_type,
                        r.discount_amount,
                        r.discount_valid_days
                    FROM '.self::TABLE_SALE.' s
                    INNER JOIN '.self::TABLE_REMINDER_ITEM.' ri
                        ON ri.product_type = s.product_type AND ri.product_id = s.product_id
                    INNER JOIN '.self::TABLE_REMINDER.' r
                        ON r.id = ri.reminder_id AND r.active = 1
                    LEFT JOIN '.self::TABLE_REMINDER_SENT.' rs
                        ON rs.reminder_id = r.id AND rs.sale_kind = ? AND rs.sale_id = s.id
                    WHERE s.status = ?
                      AND s.date >= ?
                      AND rs.id IS NULL
                    ORDER BY s.date ASC, r.days_after ASC
                    LIMIT '.$fetchLimit;
        } else {
            $sql = 'SELECT
                        s.id AS sale_id,
                        s.buyer_id AS user_id,
                        '.BuyCoursesPlugin::PRODUCT_TYPE_SERVICE.' AS product_type,
                        s.service_id AS product_id,
                        sv.name AS product_name,
                        s.status,
                        s.buy_date AS purchase_at,
                        r.id AS reminder_id,
                        r.days_after,
                        r.subject,
                        r.body,
                        r.discount_type,
                        r.discount_amount,
                        r.discount_valid_days
                    FROM '.self::TABLE_SERVICE_SALE.' s
                    INNER JOIN '.self::TABLE_SERVICES.' sv ON sv.id = s.service_id
                    INNER JOIN '.self::TABLE_REMINDER_ITEM.' ri
                        ON ri.product_type = '.BuyCoursesPlugin::PRODUCT_TYPE_SERVICE.' AND ri.product_id = s.service_id
                    INNER JOIN '.self::TABLE_REMINDER.' r
                        ON r.id = ri.reminder_id AND r.active = 1
                    LEFT JOIN '.self::TABLE_REMINDER_SENT.' rs
                        ON rs.reminder_id = r.id AND rs.sale_kind = ? AND rs.sale_id = s.id
                    WHERE s.status = ?
                      AND s.buy_date >= ?
                      AND rs.id IS NULL
                    ORDER BY s.buy_date ASC, r.days_after ASC
                    LIMIT '.$fetchLimit;
        }

        $rows = $this->connection->fetchAllAssociative(
            $sql,
            [$saleKind, BuyCoursesPlugin::SALE_STATUS_PENDING, $enabledAt->format('Y-m-d H:i:s')]
        );

        foreach ($rows as &$row) {
            $row['sale_kind'] = $saleKind;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function shouldSend(array $candidate, ?DateTimeImmutable $now = null): bool
    {
        $now ??= $this->now();
        $purchaseAt = $this->parseDate($candidate['purchase_at'] ?? null);
        if (null === $purchaseAt) {
            return false;
        }

        if (!self::isReminderDue($purchaseAt, (int) $candidate['days_after'], $now)) {
            return false;
        }

        if (BuyCoursesPlugin::SALE_STATUS_PENDING !== (int) ($candidate['status'] ?? -1)) {
            return false;
        }

        if ($this->alreadySent((int) $candidate['reminder_id'], (string) $candidate['sale_kind'], (int) $candidate['sale_id'])) {
            return false;
        }

        return !$this->mustStop($candidate, $now);
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function mustStop(array $candidate, ?DateTimeImmutable $now = null): bool
    {
        $now ??= $this->now();
        $status = (int) ($candidate['status'] ?? BuyCoursesPlugin::SALE_STATUS_PENDING);
        if (BuyCoursesPlugin::SALE_STATUS_PENDING !== $status) {
            return true;
        }

        $productType = (int) ($candidate['product_type'] ?? 0);
        $productId = (int) ($candidate['product_id'] ?? 0);

        if (!$this->isProductStillOnSale($productType, $productId)) {
            return true;
        }

        if (BuyCoursesPlugin::PRODUCT_TYPE_SESSION === $productType && $this->sessionHasStarted($productId, $now)) {
            return true;
        }

        return false;
    }

    public function isProductStillOnSale(int $productType, int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        if (BuyCoursesPlugin::PRODUCT_TYPE_SERVICE === $productType) {
            $active = $this->connection->fetchOne(
                'SELECT active FROM '.self::TABLE_SERVICES.' WHERE id = ?',
                [$productId]
            );

            return false !== $active && null !== $active && 1 === (int) $active;
        }

        $itemId = $this->connection->fetchOne(
            'SELECT id FROM '.self::TABLE_ITEM.' WHERE product_type = ? AND product_id = ?',
            [$productType, $productId]
        );

        return false !== $itemId && null !== $itemId;
    }

    public function sessionHasStarted(int $sessionId, ?DateTimeImmutable $now = null): bool
    {
        if ($sessionId <= 0) {
            return false;
        }

        $start = $this->connection->fetchOne(
            'SELECT access_start_date FROM '.self::TABLE_SESSION.' WHERE id = ?',
            [$sessionId]
        );
        $startDate = $this->parseDate($start);
        if (null === $startDate) {
            return false;
        }

        $now ??= $this->now();

        return $now >= $startDate;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function sendCandidate(array $candidate, DateTimeImmutable $now): bool
    {
        $reminderId = (int) $candidate['reminder_id'];
        $saleKind = (string) $candidate['sale_kind'];
        $saleId = (int) $candidate['sale_id'];

        if ($this->alreadySent($reminderId, $saleKind, $saleId)) {
            return false;
        }

        $couponId = null;
        if (!empty($candidate['discount_type']) && (float) ($candidate['discount_amount'] ?? 0) > 0) {
            $couponId = $this->createReminderCoupon($candidate, $now);
            if (null === $couponId) {
                return false;
            }
        }

        $url = $this->buildPurchaseLink($saleKind, $saleId, $couponId);
        $user = $this->loadUser((int) $candidate['user_id']);
        if (null === $user || '' === $user['email']) {
            return false;
        }

        $language = $this->resolveMailLanguage($user);
        $plugin = $this->plugin();
        $subject = $plugin->filterServiceMultilingualHtml((string) $candidate['subject'], $language);
        $body = $plugin->filterServiceMultilingualHtml((string) $candidate['body'], $language);
        $subject = self::sanitizeMailSubject(self::renderPlaceholder($subject, $url));
        $body = self::renderPlaceholder($body, $url);
        if ('' === $subject || '' === trim(strip_tags($body))) {
            return false;
        }

        $sent = $this->sendMail(
            trim($user['firstname'].' '.$user['lastname']),
            $user['email'],
            $subject,
            $body
        );
        if (!$sent) {
            return false;
        }

        try {
            $this->connection->insert(self::TABLE_REMINDER_SENT, [
                'reminder_id' => $reminderId,
                'sale_kind' => $saleKind,
                'sale_id' => $saleId,
                'coupon_id' => $couponId,
                'sent_at' => $now->format('Y-m-d H:i:s'),
            ]);
        } catch (DbalException) {
            // A concurrent run already recorded this (sale, reminder) pair.
            return false;
        }

        $this->expirePreviousReminderCoupons($saleKind, $saleId, $reminderId, $now);

        return true;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function createReminderCoupon(array $candidate, ?DateTimeImmutable $now = null): ?int
    {
        $now ??= $this->now();
        $validDays = (int) ($candidate['discount_valid_days'] ?? 0);
        if ($validDays <= 0) {
            $validDays = self::DEFAULT_COUPON_VALID_DAYS;
        }

        $code = $this->uniqueCouponCode();
        $this->connection->insert(self::TABLE_COUPON, [
            'code' => $code,
            'discount_type' => (int) $candidate['discount_type'],
            'discount_amount' => (float) $candidate['discount_amount'],
            'times_applied' => 1,
            'valid_start' => $now->format('Y-m-d H:i:s'),
            'valid_end' => $now->modify('+'.$validDays.' days')->format('Y-m-d H:i:s'),
            'delivered' => 0,
            'active' => 1,
            'restricted_user_id' => (int) $candidate['user_id'],
            'restricted_sale_kind' => (string) $candidate['sale_kind'],
            'restricted_sale_id' => (int) $candidate['sale_id'],
        ]);
        $couponId = (int) $this->connection->lastInsertId();
        if ($couponId <= 0) {
            return null;
        }

        $productType = (int) $candidate['product_type'];
        $productId = (int) $candidate['product_id'];
        if (BuyCoursesPlugin::PRODUCT_TYPE_SERVICE === $productType) {
            $this->connection->insert(self::TABLE_COUPON_SERVICE, [
                'coupon_id' => $couponId,
                'service_id' => $productId,
            ]);
        } else {
            $this->connection->insert(self::TABLE_COUPON_ITEM, [
                'coupon_id' => $couponId,
                'product_type' => $productType,
                'product_id' => $productId,
            ]);
        }

        return $couponId;
    }

    public function expirePreviousReminderCoupons(
        string $saleKind,
        int $saleId,
        int $currentReminderId,
        ?DateTimeImmutable $now = null,
    ): void {
        $now ??= $this->now();
        $couponIds = $this->connection->fetchFirstColumn(
            'SELECT coupon_id FROM '.self::TABLE_REMINDER_SENT.'
             WHERE sale_kind = ? AND sale_id = ? AND reminder_id <> ? AND coupon_id IS NOT NULL',
            [$saleKind, $saleId, $currentReminderId]
        );

        foreach ($couponIds as $couponId) {
            $couponId = (int) $couponId;
            if ($couponId <= 0) {
                continue;
            }
            $this->connection->update(self::TABLE_COUPON, [
                'active' => 0,
                'valid_end' => $now->format('Y-m-d H:i:s'),
            ], ['id' => $couponId]);
        }
    }

    public function alreadySent(int $reminderId, string $saleKind, int $saleId): bool
    {
        if (!$this->hasReminderInfrastructure()) {
            return false;
        }

        $id = $this->connection->fetchOne(
            'SELECT id FROM '.self::TABLE_REMINDER_SENT.'
             WHERE reminder_id = ? AND sale_kind = ? AND sale_id = ?',
            [$reminderId, $saleKind, $saleId]
        );

        return false !== $id && null !== $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadSale(string $saleKind, int $saleId): ?array
    {
        if (self::SALE_KIND_SALE === $saleKind) {
            $row = $this->connection->fetchAssociative(
                'SELECT id, user_id, product_type, product_id, product_name, status, date AS purchase_at,
                        price, price_without_discount, discount_amount
                 FROM '.self::TABLE_SALE.' WHERE id = ?',
                [$saleId]
            );

            return \is_array($row) ? $row : null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT s.id, s.buyer_id AS user_id, '.BuyCoursesPlugin::PRODUCT_TYPE_SERVICE.' AS product_type,
                    s.service_id AS product_id, sv.name AS product_name, s.status, s.buy_date AS purchase_at,
                    s.price, s.price_without_discount, s.discount_amount
             FROM '.self::TABLE_SERVICE_SALE.' s
             LEFT JOIN '.self::TABLE_SERVICES.' sv ON sv.id = s.service_id
             WHERE s.id = ?',
            [$saleId]
        );

        return \is_array($row) ? $row : null;
    }

    /**
     * @return array{id: int, email: string, firstname: string, lastname: string, locale: string}|null
     */
    private function loadUser(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $row = $this->connection->fetchAssociative(
            'SELECT id, email, firstname, lastname, locale FROM '.self::TABLE_USER.' WHERE id = ?',
            [$userId]
        );

        return \is_array($row) ? $row : null;
    }

    /**
     * @param array{id?: int, email?: string, firstname?: string, lastname?: string, locale?: string} $user
     */
    private function resolveMailLanguage(array $user): string
    {
        $locale = trim((string) ($user['locale'] ?? ''));
        if ('' !== $locale) {
            return $locale;
        }

        if (\function_exists('api_get_setting')) {
            return (string) api_get_setting('platformLanguage');
        }

        return 'en_US';
    }

    private function sendMail(string $name, string $email, string $subject, string $body): bool
    {
        if (\is_callable($this->mailSender)) {
            return (bool) ($this->mailSender)($name, $email, $subject, $body);
        }

        if (!\function_exists('api_mail_html')) {
            return false;
        }

        return (bool) api_mail_html($name, $email, $subject, $body);
    }

    private function uniqueCouponCode(): string
    {
        for ($i = 0; $i < 10; ++$i) {
            $code = 'RM'.strtoupper(bin2hex(random_bytes(6)));
            $exists = $this->connection->fetchOne(
                'SELECT id FROM '.self::TABLE_COUPON.' WHERE code = ?',
                [$code]
            );
            if (false === $exists || null === $exists) {
                return $code;
            }
        }

        return 'RM'.strtoupper(bin2hex(random_bytes(10)));
    }

    /**
     * @param array<string, mixed> $coupon
     */
    private function computeDiscount(array $coupon, float $basePrice): float
    {
        $amount = (float) ($coupon['discount_amount'] ?? 0);
        if (BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE === (int) ($coupon['discount_type'] ?? 0)) {
            $amount = ($basePrice * $amount) / 100;
        }

        return min($amount, $basePrice);
    }

    private function ensureCouponSaleLink(string $table, string $saleColumn, int $couponId, int $saleId): void
    {
        $existing = $this->connection->fetchOne(
            "SELECT id FROM $table WHERE coupon_id = ? AND $saleColumn = ?",
            [$couponId, $saleId]
        );
        if (false !== $existing && null !== $existing) {
            return;
        }

        $values = [
            'coupon_id' => $couponId,
            $saleColumn => $saleId,
        ];
        if (self::TABLE_COUPON_SERVICE_SALE === $table) {
            $values['applied_count'] = 1;
        }
        $this->connection->insert($table, $values);
    }

    /**
     * @return list<array{product_type: int, product_id: int}>
     */
    private function getReminderProducts(int $reminderId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT product_type, product_id FROM '.self::TABLE_REMINDER_ITEM.' WHERE reminder_id = ? ORDER BY product_type ASC, product_id ASC',
            [$reminderId]
        );

        return $this->normalizeProducts($rows);
    }

    /**
     * @param mixed $products
     *
     * @return list<array{product_type: int, product_id: int}>
     */
    private function normalizeProducts($products): array
    {
        if (!\is_array($products)) {
            return [];
        }

        $normalized = [];
        foreach ($products as $product) {
            if (!\is_array($product)) {
                continue;
            }
            $type = (int) ($product['product_type'] ?? 0);
            $id = (int) ($product['product_id'] ?? 0);
            if (
                $id <= 0
                || !\in_array(
                    $type,
                    [
                        BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
                        BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
                        BuyCoursesPlugin::PRODUCT_TYPE_SERVICE,
                    ],
                    true
                )
            ) {
                continue;
            }
            $normalized[$type.'-'.$id] = ['product_type' => $type, 'product_id' => $id];
        }

        return array_values($normalized);
    }

    private function hasReminderInfrastructure(): bool
    {
        return $this->tableExists(self::TABLE_REMINDER)
            && $this->tableExists(self::TABLE_REMINDER_ITEM)
            && $this->tableExists(self::TABLE_REMINDER_SENT);
    }

    private function tableExists(string $table): bool
    {
        try {
            $result = $this->connection->fetchOne(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$table]
            );

            return false !== $result && null !== $result;
        } catch (Throwable) {
            return false;
        }
    }

    private function hasGlobalConfigColumn(string $column): bool
    {
        try {
            $result = $this->connection->fetchOne(
                'SELECT 1 FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                [self::TABLE_GLOBAL_CONFIG, $column]
            );

            return false !== $result && null !== $result;
        } catch (Throwable) {
            return false;
        }
    }

    private function ensureGlobalConfigRow(): void
    {
        $id = $this->connection->fetchOne('SELECT id FROM '.self::TABLE_GLOBAL_CONFIG.' ORDER BY id ASC LIMIT 1');
        if (false !== $id && null !== $id) {
            return;
        }

        $this->connection->insert(self::TABLE_GLOBAL_CONFIG, []);
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }
        if (!\is_string($value) || '' === trim($value) || '0000-00-00 00:00:00' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (Throwable) {
            return null;
        }
    }

    private function plugin(): BuyCoursesPlugin
    {
        return $this->pluginInstance ?? BuyCoursesPlugin::create();
    }
}
