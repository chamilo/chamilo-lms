<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * PayPal IPN endpoint for BuyCourses recurring service payments.
 *
 * This endpoint is intentionally session-free. PayPal calls it server-to-server.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$paypalParams = $plugin->getPaypalParams();
$isSandbox = 1 === (int) ($paypalParams['sandbox'] ?? 0);

$rawBody = (string) file_get_contents('php://input');
$postData = $_POST;

if (empty($postData) && '' !== $rawBody) {
    parse_str($rawBody, $postData);
}

$respond = static function (string $message, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
};

$log = static function (string $message, array $context = []): void {
    $suffix = '';
    if (!empty($context)) {
        $suffix = ' | '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    error_log('[BuyCourses][Recurring IPN] '.$message.$suffix);
};

if (empty($postData)) {
    $log('Empty IPN payload rejected.');
    $respond('EMPTY', 400);
}

$verifyBody = 'cmd=_notify-validate';
foreach ($postData as $key => $value) {
    if (is_array($value)) {
        continue;
    }

    $verifyBody .= '&'.urlencode((string) $key).'='.urlencode((string) $value);
}

$verifyUrl = $isSandbox
    ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
    : 'https://ipnpb.paypal.com/cgi-bin/webscr';

$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $verifyBody);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);

$verificationResponse = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrorNumber = curl_errno($ch);
curl_close($ch);

if (false === $verificationResponse) {
    $log('Unable to validate IPN with PayPal.', [
        'curl_error_number' => $curlErrorNumber,
        'curl_error' => $curlError,
        'sandbox' => $isSandbox,
    ]);

    $respond('VALIDATION_ERROR', 500);
}

if ('VERIFIED' !== trim((string) $verificationResponse)) {
    $log('Invalid IPN payload rejected.', [
        'paypal_response' => trim((string) $verificationResponse),
        'txn_type' => (string) ($postData['txn_type'] ?? ''),
        'profile_id' => (string) ($postData['recurring_payment_id'] ?? $postData['profile_id'] ?? ''),
        'sandbox' => $isSandbox,
    ]);

    $respond('INVALID', 400);
}

$txnType = strtolower((string) ($postData['txn_type'] ?? ''));
$paymentStatus = strtolower((string) ($postData['payment_status'] ?? ''));
$profileStatus = strtolower((string) ($postData['profile_status'] ?? ''));
$profileId = trim((string) (
    $postData['recurring_payment_id']
    ?? $postData['recurring_payment_profile_id']
    ?? $postData['profile_id']
    ?? ''
));
$txnId = trim((string) ($postData['txn_id'] ?? ''));
$ipnTrackId = trim((string) ($postData['ipn_track_id'] ?? ''));
$eventKey = '' !== $txnId ? 'txn:'.$txnId : ('' !== $ipnTrackId ? 'track:'.$ipnTrackId : 'hash:'.sha1(json_encode($postData)));

if ('' === $profileId) {
    $log('Verified IPN ignored because no recurring profile ID was found.', [
        'txn_type' => $txnType,
        'payment_status' => $paymentStatus,
        'event_key' => $eventKey,
    ]);

    $respond('IGNORED');
}

$logTable = Database::get_main_table('plugin_buycourses_recurring_ipn_log');
Database::query(
    "CREATE TABLE IF NOT EXISTS $logTable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_key VARCHAR(128) NOT NULL,
        profile_id VARCHAR(255) NOT NULL,
        txn_id VARCHAR(255) DEFAULT NULL,
        txn_type VARCHAR(128) DEFAULT NULL,
        payment_status VARCHAR(64) DEFAULT NULL,
        raw_payload LONGTEXT NOT NULL,
        processed_at DATETIME NOT NULL,
        UNIQUE KEY uniq_event_key (event_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$eventKeySql = Database::escape_string($eventKey);
$profileIdSql = Database::escape_string($profileId);
$txnIdSql = Database::escape_string($txnId);
$txnTypeSql = Database::escape_string($txnType);
$paymentStatusSql = Database::escape_string($paymentStatus);
$rawPayloadSql = Database::escape_string(json_encode($postData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
$nowSql = Database::escape_string(api_get_utc_datetime());

$insertResult = Database::query(
    "INSERT IGNORE INTO $logTable
        (event_key, profile_id, txn_id, txn_type, payment_status, raw_payload, processed_at)
     VALUES
        ('$eventKeySql', '$profileIdSql', '$txnIdSql', '$txnTypeSql', '$paymentStatusSql', '$rawPayloadSql', '$nowSql')"
);

if (false === $insertResult) {
    $log('Unable to insert IPN event log.', [
        'event_key' => $eventKey,
        'profile_id' => $profileId,
    ]);

    $respond('LOG_ERROR', 500);
}

$duplicateCheck = Database::query("SELECT ROW_COUNT() AS inserted_count");
$duplicateRow = $duplicateCheck ? Database::fetch_array($duplicateCheck, 'ASSOC') : ['inserted_count' => 1];
if (0 === (int) ($duplicateRow['inserted_count'] ?? 1)) {
    $log('Duplicate IPN event ignored.', [
        'event_key' => $eventKey,
        'profile_id' => $profileId,
        'txn_type' => $txnType,
    ]);

    $respond('DUPLICATE');
}

$serviceSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES_SALE);
$serviceTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES);
$subscriptionCourseTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SUBSCRIPTION_COURSE);
$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
$defaultActiveCourseVisibility = defined('COURSE_VISIBILITY_REGISTERED') ? (int) COURSE_VISIBILITY_REGISTERED : 2;

$sale = Database::select(
    'ss.*, s.duration_days, s.total_charges, s.renewable',
    "$serviceSaleTable ss INNER JOIN $serviceTable s ON ss.service_id = s.id",
    [
        'WHERE' => [
            'ss.recurring_profile_id = ?' => $profileId,
        ],
    ],
    'first'
);

if (empty($sale)) {
    $log('Verified IPN ignored because no service sale matches the profile ID.', [
        'profile_id' => $profileId,
        'txn_type' => $txnType,
        'event_key' => $eventKey,
    ]);

    $respond('NO_SALE');
}

$serviceSaleId = (int) ($sale['id'] ?? 0);
$durationDays = max(1, (int) ($sale['duration_days'] ?? 1));


$decodeContext = static function (?string $contextJson): array {
    if (empty($contextJson)) {
        return [];
    }

    $decoded = json_decode($contextJson, true);

    return is_array($decoded) ? $decoded : [];
};

$encodeContextForSql = static function (array $context): string {
    return Database::escape_string(json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
};

$reactivateCoursesForSubscriptionSale = static function (
    int $serviceSaleId,
    string $subscriptionCourseTable,
    string $courseTable,
    int $defaultActiveCourseVisibility,
    callable $decodeContext,
    callable $encodeContextForSql,
    callable $log
): array {
    if ($serviceSaleId <= 0) {
        return ['found' => 0, 'reactivated' => 0, 'errors' => 0];
    }

    $now = Database::escape_string(api_get_utc_datetime());
    $sql = "SELECT id, course_id, status, context_json
        FROM $subscriptionCourseTable
        WHERE service_sale_id = $serviceSaleId
          AND status IN ('closed', 'hidden')
        ORDER BY id ASC";

    $result = Database::query($sql);

    if (false === $result) {
        $log('Failed to read closed courses for recurring sale reactivation.', [
            'service_sale_id' => $serviceSaleId,
        ]);

        return ['found' => 0, 'reactivated' => 0, 'errors' => 1];
    }

    $found = 0;
    $reactivated = 0;
    $errors = 0;

    while ($row = Database::fetch_assoc($result)) {
        ++$found;

        $subscriptionCourseId = (int) ($row['id'] ?? 0);
        $courseId = (int) ($row['course_id'] ?? 0);
        $context = $decodeContext((string) ($row['context_json'] ?? ''));
        $previousVisibility = isset($context['previous_visibility']) ? (int) $context['previous_visibility'] : $defaultActiveCourseVisibility;

        if ($previousVisibility <= 0) {
            $previousVisibility = $defaultActiveCourseVisibility;
        }

        if ($subscriptionCourseId <= 0 || $courseId <= 0) {
            ++$errors;
            $log('Invalid closed subscription course row while reactivating courses.', [
                'service_sale_id' => $serviceSaleId,
                'subscription_course_id' => $subscriptionCourseId,
                'course_id' => $courseId,
            ]);

            continue;
        }

        $context['reactivated_at'] = $now;
        $context['last_action'] = 'reactivated';
        $contextJson = $encodeContextForSql($context);

        $courseUpdate = Database::query(
            "UPDATE $courseTable
                SET visibility = $previousVisibility
                WHERE id = $courseId
                LIMIT 1"
        );

        if (false === $courseUpdate) {
            ++$errors;
            $log('Failed to restore course visibility after recurring payment completion.', [
                'service_sale_id' => $serviceSaleId,
                'course_id' => $courseId,
                'visibility' => $previousVisibility,
            ]);

            continue;
        }

        $subscriptionCourseUpdate = Database::query(
            "UPDATE $subscriptionCourseTable
                SET status = 'active',
                    context_json = '$contextJson',
                    closed_at = NULL,
                    hidden_at = NULL,
                    deleted_at = NULL,
                    updated_at = '$now',
                    last_action = 'reactivated'
                WHERE id = $subscriptionCourseId
                LIMIT 1"
        );

        if (false === $subscriptionCourseUpdate) {
            ++$errors;
            $log('Course visibility was restored but subscription course row could not be updated.', [
                'service_sale_id' => $serviceSaleId,
                'subscription_course_id' => $subscriptionCourseId,
                'course_id' => $courseId,
            ]);

            continue;
        }

        ++$reactivated;
    }

    return ['found' => $found, 'reactivated' => $reactivated, 'errors' => $errors];
};

$extendServiceSale = static function (array $saleRow) use ($plugin, $serviceSaleTable, $serviceSaleId, $durationDays, $profileId, $log, $reactivateCoursesForSubscriptionSale, $subscriptionCourseTable, $courseTable, $defaultActiveCourseVisibility, $decodeContext, $encodeContextForSql): void {
    $currentEnd = new DateTimeImmutable((string) ($saleRow['date_end'] ?? 'now'), new DateTimeZone('UTC'));
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $baseDate = $currentEnd > $now ? $currentEnd : $now;
    $newEnd = $baseDate->modify('+'.$durationDays.' days');
    $newEndSql = $newEnd->format('Y-m-d H:i:s');

    Database::update(
        $serviceSaleTable,
        [
            'date_end' => $newEndSql,
            'next_charge_date' => $newEndSql,
            'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED,
            'cancelled_at' => null,
            'status' => BuyCoursesPlugin::SERVICE_STATUS_COMPLETED,
        ],
        ['id = ?' => $serviceSaleId]
    );

    $plugin->applyServiceBenefitsFromSale($serviceSaleId);

    $reactivationResult = $reactivateCoursesForSubscriptionSale(
        $serviceSaleId,
        $subscriptionCourseTable,
        $courseTable,
        $defaultActiveCourseVisibility,
        $decodeContext,
        $encodeContextForSql,
        $log
    );

    $log('Recurring payment completed and service sale extended.', [
        'service_sale_id' => $serviceSaleId,
        'profile_id' => $profileId,
        'new_date_end' => $newEndSql,
        'courses_reactivation_found' => (int) $reactivationResult['found'],
        'courses_reactivated' => (int) $reactivationResult['reactivated'],
        'reactivation_errors' => (int) $reactivationResult['errors'],
    ]);
};

$markRecurringStatus = static function (int $status, ?string $cancelledAt = null) use ($serviceSaleTable, $serviceSaleId): void {
    $values = [
        'recurring_payment' => $status,
    ];

    if (null !== $cancelledAt) {
        $values['cancelled_at'] = $cancelledAt;
    }

    Database::update(
        $serviceSaleTable,
        $values,
        ['id = ?' => $serviceSaleId]
    );
};

if ('recurring_payment' === $txnType && 'completed' === $paymentStatus) {
    $extendServiceSale($sale);
    $respond('OK');
}

if (in_array($txnType, ['recurring_payment_failed', 'recurring_payment_skipped'], true)) {
    $markRecurringStatus(BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_SUSPENDED);

    $log('Recurring payment failed or skipped.', [
        'service_sale_id' => $serviceSaleId,
        'profile_id' => $profileId,
        'txn_type' => $txnType,
        'payment_status' => $paymentStatus,
    ]);

    $respond('OK');
}

if (in_array($txnType, ['recurring_payment_profile_cancel', 'recurring_payment_profile_cancelled'], true)) {
    $markRecurringStatus(BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED, api_get_utc_datetime());

    $log('Recurring payment profile cancelled.', [
        'service_sale_id' => $serviceSaleId,
        'profile_id' => $profileId,
        'txn_type' => $txnType,
    ]);

    $respond('OK');
}

if ('recurring_payment_profile_created' === $txnType) {
    $markRecurringStatus(BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED);

    $log('Recurring payment profile creation confirmed.', [
        'service_sale_id' => $serviceSaleId,
        'profile_id' => $profileId,
        'profile_status' => $profileStatus,
    ]);

    $respond('OK');
}

if (in_array($profileStatus, ['cancelled', 'canceled'], true)) {
    $markRecurringStatus(BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED, api_get_utc_datetime());
    $respond('OK');
}

if ('suspended' === $profileStatus) {
    $markRecurringStatus(BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_SUSPENDED);
    $respond('OK');
}

$log('Verified IPN ignored because txn_type is not handled.', [
    'service_sale_id' => $serviceSaleId,
    'profile_id' => $profileId,
    'txn_type' => $txnType,
    'payment_status' => $paymentStatus,
    'profile_status' => $profileStatus,
]);

$respond('IGNORED');
