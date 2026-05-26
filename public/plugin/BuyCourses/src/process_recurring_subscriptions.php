<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

/**
 * Fallback recurring subscriptions processor for BuyCourses services.
 *
 * This script does not replace PayPal IPN.
 * It marks expired recurring service sales as suspended when no successful IPN extended them,
 * closes only courses created under the expired subscription, hides them after the configured
 * grace period and reactivates them when the subscription becomes active again.
 *
 * Usage:
 *   php public/plugin/BuyCourses/src/process_recurring_subscriptions.php
 *   php public/plugin/BuyCourses/src/process_recurring_subscriptions.php --dry-run
 */

$cidReset = true;

require_once __DIR__.'/../config.php';

$plugin = BuyCoursesPlugin::create();

$isCli = 'cli' === PHP_SAPI;
$isDryRun = $isCli && in_array('--dry-run', $argv ?? [], true);

if (!$isCli && !api_is_platform_admin()) {
    api_not_allowed(true);
}

$includeServices = 'true' === $plugin->get('include_services');

if (!$includeServices) {
    $message = '[BuyCourses][Recurring Cron] Services are disabled. Nothing to process.';

    if ($isCli) {
        echo $message.PHP_EOL;
        exit(0);
    }

    error_log($message);
    Display::addFlash(Display::return_message($message, 'warning', false));
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_panel.php');
    exit;
}

$serviceSaleTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES_SALE);
$serviceTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SERVICES);
$subscriptionCourseTable = Database::get_main_table(BuyCoursesPlugin::TABLE_SUBSCRIPTION_COURSE);
$courseTable = Database::get_main_table(TABLE_MAIN_COURSE);

$now = Database::escape_string(api_get_utc_datetime());
$completedStatus = BuyCoursesPlugin::SERVICE_STATUS_COMPLETED;
$enabledRecurringStatus = BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED;
$suspendedRecurringStatus = BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_SUSPENDED;
$cancelledRecurringStatus = BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED;
$closedCourseVisibility = defined('COURSE_VISIBILITY_CLOSED') ? (int) COURSE_VISIBILITY_CLOSED : 0;
$hiddenCourseVisibility = defined('COURSE_VISIBILITY_HIDDEN') ? (int) COURSE_VISIBILITY_HIDDEN : 4;
$defaultActiveCourseVisibility = defined('COURSE_VISIBILITY_OPEN_PLATFORM') ? (int) COURSE_VISIBILITY_OPEN_PLATFORM : 2;

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

$decodeSubscriptionBehavior = static function (?string $behaviorJson): array {
    $defaults = [
        'close_after_days' => 0,
        'hide_after_days' => 30,
        'delete_after_days' => null,
    ];

    if (empty($behaviorJson)) {
        return $defaults;
    }

    $decoded = json_decode($behaviorJson, true);

    if (!is_array($decoded)) {
        return $defaults;
    }

    foreach (['close_after_days', 'hide_after_days', 'delete_after_days'] as $key) {
        if (!array_key_exists($key, $decoded)) {
            continue;
        }

        if (null === $decoded[$key] || '' === $decoded[$key]) {
            $defaults[$key] = null;
            continue;
        }

        if (is_numeric($decoded[$key])) {
            $defaults[$key] = max(0, (int) $decoded[$key]);
        }
    }

    return $defaults;
};

/**
 * Close courses linked to one expired subscription sale.
 */
$closeCoursesForSubscriptionSale = static function (
    int $serviceSaleId,
    string $subscriptionCourseTable,
    string $courseTable,
    int $closedCourseVisibility,
    string $now,
    bool $isDryRun,
    callable $decodeContext,
    callable $encodeContextForSql
): array {
    $serviceSaleId = (int) $serviceSaleId;

    if ($serviceSaleId <= 0) {
        return ['found' => 0, 'closed' => 0, 'errors' => 0];
    }

    $sql = "SELECT sc.id, sc.course_id, sc.status, sc.context_json, c.visibility AS current_visibility
        FROM $subscriptionCourseTable sc
        INNER JOIN $courseTable c ON c.id = sc.course_id
        WHERE sc.service_sale_id = $serviceSaleId
          AND sc.status = 'active'
        ORDER BY sc.id ASC";

    $result = Database::query($sql);

    if (false === $result) {
        error_log('[BuyCourses][Recurring Cron] Failed to read subscription courses for service sale '.$serviceSaleId);

        return ['found' => 0, 'closed' => 0, 'errors' => 1];
    }

    $found = 0;
    $closed = 0;
    $errors = 0;

    while ($row = Database::fetch_assoc($result)) {
        ++$found;

        $subscriptionCourseId = (int) ($row['id'] ?? 0);
        $courseId = (int) ($row['course_id'] ?? 0);
        $currentVisibility = (int) ($row['current_visibility'] ?? 0);

        if ($subscriptionCourseId <= 0 || $courseId <= 0) {
            ++$errors;
            error_log(
                '[BuyCourses][Recurring Cron] Invalid subscription course row. service_sale_id='.
                $serviceSaleId.
                ' subscription_course_id='.
                $subscriptionCourseId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        error_log(
            '[BuyCourses][Recurring Cron] Closing course created under expired subscription. service_sale_id='.
            $serviceSaleId.
            ' subscription_course_id='.
            $subscriptionCourseId.
            ' course_id='.
            $courseId
        );

        if ($isDryRun) {
            continue;
        }

        $context = $decodeContext((string) ($row['context_json'] ?? ''));
        if (!isset($context['previous_visibility']) || (int) $context['previous_visibility'] <= 0) {
            $context['previous_visibility'] = $currentVisibility;
        }
        $context['closed_visibility'] = $closedCourseVisibility;
        $context['closed_at'] = $now;
        $context['last_action'] = 'closed';
        $contextJson = $encodeContextForSql($context);

        $courseUpdate = Database::query(
            "UPDATE $courseTable
                SET visibility = $closedCourseVisibility
                WHERE id = $courseId
                LIMIT 1"
        );

        if (false === $courseUpdate) {
            ++$errors;
            error_log(
                '[BuyCourses][Recurring Cron] Failed to close course. service_sale_id='.
                $serviceSaleId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        $subscriptionCourseUpdate = Database::query(
            "UPDATE $subscriptionCourseTable
                SET status = 'closed',
                    context_json = '$contextJson',
                    closed_at = '$now',
                    updated_at = '$now',
                    last_action = 'closed'
                WHERE id = $subscriptionCourseId
                LIMIT 1"
        );

        if (false === $subscriptionCourseUpdate) {
            ++$errors;
            error_log(
                '[BuyCourses][Recurring Cron] Course was closed but subscription course row could not be updated. service_sale_id='.
                $serviceSaleId.
                ' subscription_course_id='.
                $subscriptionCourseId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        ++$closed;
    }

    return ['found' => $found, 'closed' => $closed, 'errors' => $errors];
};

/**
 * Hide courses that have remained closed after the configured grace period.
 */
$hideClosedCoursesForSubscriptionSale = static function (
    int $serviceSaleId,
    int $hideAfterDays,
    string $subscriptionCourseTable,
    string $courseTable,
    int $hiddenCourseVisibility,
    string $now,
    bool $isDryRun,
    callable $decodeContext,
    callable $encodeContextForSql
): array {
    $serviceSaleId = (int) $serviceSaleId;
    $hideAfterDays = (int) $hideAfterDays;

    if ($serviceSaleId <= 0 || $hideAfterDays < 0) {
        return ['found' => 0, 'hidden' => 0, 'errors' => 0];
    }

    $sql = "SELECT sc.id, sc.course_id, sc.closed_at, sc.context_json, c.visibility AS current_visibility
        FROM $subscriptionCourseTable sc
        INNER JOIN $courseTable c ON c.id = sc.course_id
        WHERE sc.service_sale_id = $serviceSaleId
          AND sc.status = 'closed'
          AND sc.closed_at IS NOT NULL
          AND sc.closed_at <= DATE_SUB('$now', INTERVAL $hideAfterDays DAY)
        ORDER BY sc.id ASC";

    $result = Database::query($sql);

    if (false === $result) {
        error_log('[BuyCourses][Recurring Cron] Failed to read closed courses for hiding. service_sale_id='.$serviceSaleId);

        return ['found' => 0, 'hidden' => 0, 'errors' => 1];
    }

    $found = 0;
    $hidden = 0;
    $errors = 0;

    while ($row = Database::fetch_assoc($result)) {
        ++$found;

        $subscriptionCourseId = (int) ($row['id'] ?? 0);
        $courseId = (int) ($row['course_id'] ?? 0);

        if ($subscriptionCourseId <= 0 || $courseId <= 0) {
            ++$errors;
            error_log(
                '[BuyCourses][Recurring Cron] Invalid closed subscription course row for hiding. service_sale_id='.
                $serviceSaleId.
                ' subscription_course_id='.
                $subscriptionCourseId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        error_log(
            '[BuyCourses][Recurring Cron] Hiding course after subscription grace period. service_sale_id='.
            $serviceSaleId.
            ' subscription_course_id='.
            $subscriptionCourseId.
            ' course_id='.
            $courseId.
            ' hide_after_days='.
            $hideAfterDays
        );

        if ($isDryRun) {
            continue;
        }

        $context = $decodeContext((string) ($row['context_json'] ?? ''));
        $context['hidden_visibility'] = $hiddenCourseVisibility;
        $context['hidden_at'] = $now;
        $context['last_action'] = 'hidden';
        $contextJson = $encodeContextForSql($context);

        $courseUpdate = Database::query(
            "UPDATE $courseTable
                SET visibility = $hiddenCourseVisibility
                WHERE id = $courseId
                LIMIT 1"
        );

        if (false === $courseUpdate) {
            ++$errors;
            error_log(
                '[BuyCourses][Recurring Cron] Failed to hide course. service_sale_id='.
                $serviceSaleId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        $subscriptionCourseUpdate = Database::query(
            "UPDATE $subscriptionCourseTable
                SET status = 'hidden',
                    context_json = '$contextJson',
                    hidden_at = '$now',
                    updated_at = '$now',
                    last_action = 'hidden'
                WHERE id = $subscriptionCourseId
                LIMIT 1"
        );

        if (false === $subscriptionCourseUpdate) {
            ++$errors;
            error_log(
                '[BuyCourses][Recurring Cron] Course was hidden but subscription course row could not be updated. service_sale_id='.
                $serviceSaleId.
                ' subscription_course_id='.
                $subscriptionCourseId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        ++$hidden;
    }

    return ['found' => $found, 'hidden' => $hidden, 'errors' => $errors];
};

/**
 * Reactivate courses linked to one renewed subscription sale.
 */
$reactivateCoursesForSubscriptionSale = static function (
    int $serviceSaleId,
    string $subscriptionCourseTable,
    string $courseTable,
    int $defaultActiveCourseVisibility,
    string $now,
    bool $isDryRun,
    callable $decodeContext,
    callable $encodeContextForSql
): array {
    $serviceSaleId = (int) $serviceSaleId;

    if ($serviceSaleId <= 0) {
        return ['found' => 0, 'reactivated' => 0, 'errors' => 0];
    }

    $sql = "SELECT id, course_id, status, context_json
        FROM $subscriptionCourseTable
        WHERE service_sale_id = $serviceSaleId
          AND status IN ('closed', 'hidden')
        ORDER BY id ASC";

    $result = Database::query($sql);

    if (false === $result) {
        error_log('[BuyCourses][Recurring Cron] Failed to read closed subscription courses for service sale '.$serviceSaleId);

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
            error_log(
                '[BuyCourses][Recurring Cron] Invalid closed subscription course row. service_sale_id='.
                $serviceSaleId.
                ' subscription_course_id='.
                $subscriptionCourseId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        error_log(
            '[BuyCourses][Recurring Cron] Reactivating course after subscription renewal. service_sale_id='.
            $serviceSaleId.
            ' subscription_course_id='.
            $subscriptionCourseId.
            ' course_id='.
            $courseId.
            ' visibility='.
            $previousVisibility
        );

        if ($isDryRun) {
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
            error_log(
                '[BuyCourses][Recurring Cron] Failed to reactivate course. service_sale_id='.
                $serviceSaleId.
                ' course_id='.
                $courseId
            );

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
            error_log(
                '[BuyCourses][Recurring Cron] Course was reactivated but subscription course row could not be updated. service_sale_id='.
                $serviceSaleId.
                ' subscription_course_id='.
                $subscriptionCourseId.
                ' course_id='.
                $courseId
            );

            continue;
        }

        ++$reactivated;
    }

    return ['found' => $found, 'reactivated' => $reactivated, 'errors' => $errors];
};

$expiredSql = "
    SELECT
        ss.id,
        ss.service_id,
        ss.reference,
        ss.buyer_id,
        ss.date_start,
        ss.date_end,
        ss.next_charge_date,
        ss.recurring_payment,
        ss.recurring_profile_id,
        s.name AS service_name,
        s.duration_days,
        s.subscription_behavior_json
    FROM $serviceSaleTable ss
    INNER JOIN $serviceTable s ON s.id = ss.service_id
    WHERE
        ss.status = $completedStatus
        AND s.renewable = 1
        AND ss.recurring_payment IN ($enabledRecurringStatus, $suspendedRecurringStatus, $cancelledRecurringStatus)
        AND ss.date_end < '$now'
";

$expiredResult = Database::query($expiredSql);

$processed = 0;
$suspended = 0;
$coursesFound = 0;
$coursesClosed = 0;
$coursesHideFound = 0;
$coursesHidden = 0;
$reactivationProcessed = 0;
$coursesReactivationFound = 0;
$coursesReactivated = 0;
$errors = 0;

while ($sale = Database::fetch_assoc($expiredResult)) {
    ++$processed;

    $saleId = (int) $sale['id'];
    $recurringPayment = (int) ($sale['recurring_payment'] ?? 0);
    $profileId = trim((string) ($sale['recurring_profile_id'] ?? ''));
    $reference = (string) ($sale['reference'] ?? '');
    $serviceName = (string) ($sale['service_name'] ?? '');
    $dateEnd = (string) ($sale['date_end'] ?? '');
    $behavior = $decodeSubscriptionBehavior((string) ($sale['subscription_behavior_json'] ?? ''));

    $logContext = 'service_sale_id='.$saleId.
        ' reference='.$reference.
        ' profile_id='.$profileId.
        ' service="'.$serviceName.'"'.
        ' date_end='.$dateEnd.
        ' recurring_payment='.$recurringPayment;

    if ($enabledRecurringStatus === $recurringPayment) {
        if ('' === $profileId) {
            error_log('[BuyCourses][Recurring Cron] Expired renewable sale has no profile ID. Marking as suspended. '.$logContext);
        } else {
            error_log('[BuyCourses][Recurring Cron] Expired recurring sale found. Marking as suspended. '.$logContext);
        }

        if (!$isDryRun) {
            $updateSql = "
                UPDATE $serviceSaleTable
                SET recurring_payment = $suspendedRecurringStatus
                WHERE id = $saleId
                LIMIT 1
            ";

            $updated = Database::query($updateSql);

            if (false === $updated) {
                ++$errors;
                error_log('[BuyCourses][Recurring Cron] Failed to suspend recurring sale. '.$logContext);

                continue;
            }

            ++$suspended;
        }
    } else {
        error_log('[BuyCourses][Recurring Cron] Expired recurring sale already inactive. Processing linked courses. '.$logContext);
    }

    $closeResult = $closeCoursesForSubscriptionSale(
        $saleId,
        $subscriptionCourseTable,
        $courseTable,
        $closedCourseVisibility,
        $now,
        $isDryRun,
        $decodeContext,
        $encodeContextForSql
    );

    $coursesFound += (int) $closeResult['found'];
    $coursesClosed += (int) $closeResult['closed'];
    $errors += (int) $closeResult['errors'];

    if (null !== $behavior['hide_after_days']) {
        $hideResult = $hideClosedCoursesForSubscriptionSale(
            $saleId,
            (int) $behavior['hide_after_days'],
            $subscriptionCourseTable,
            $courseTable,
            $hiddenCourseVisibility,
            $now,
            $isDryRun,
            $decodeContext,
            $encodeContextForSql
        );

        $coursesHideFound += (int) $hideResult['found'];
        $coursesHidden += (int) $hideResult['hidden'];
        $errors += (int) $hideResult['errors'];
    }

    if (null !== $behavior['delete_after_days']) {
        error_log(
            '[BuyCourses][Recurring Cron] delete_after_days is configured but delete action is intentionally not implemented yet. service_sale_id='.
            $saleId.
            ' delete_after_days='.
            (int) $behavior['delete_after_days']
        );
    }
}

$activeSql = "
    SELECT
        ss.id,
        ss.reference,
        ss.date_end,
        ss.recurring_profile_id,
        s.name AS service_name
    FROM $serviceSaleTable ss
    INNER JOIN $serviceTable s ON s.id = ss.service_id
    WHERE
        ss.status = $completedStatus
        AND s.renewable = 1
        AND ss.recurring_payment = $enabledRecurringStatus
        AND ss.date_end >= '$now'
";

$activeResult = Database::query($activeSql);

while ($sale = Database::fetch_assoc($activeResult)) {
    $saleId = (int) $sale['id'];

    $reactivateResult = $reactivateCoursesForSubscriptionSale(
        $saleId,
        $subscriptionCourseTable,
        $courseTable,
        $defaultActiveCourseVisibility,
        $now,
        $isDryRun,
        $decodeContext,
        $encodeContextForSql
    );

    if ((int) $reactivateResult['found'] > 0) {
        ++$reactivationProcessed;
        error_log(
            '[BuyCourses][Recurring Cron] Active recurring sale has closed or hidden courses to reactivate. service_sale_id='.
            $saleId.
            ' reference='.
            (string) ($sale['reference'] ?? '').
            ' service="'.
            (string) ($sale['service_name'] ?? '').
            '" date_end='.
            (string) ($sale['date_end'] ?? '')
        );
    }

    $coursesReactivationFound += (int) $reactivateResult['found'];
    $coursesReactivated += (int) $reactivateResult['reactivated'];
    $errors += (int) $reactivateResult['errors'];
}

$summary = sprintf(
    '[BuyCourses][Recurring Cron] Finished. processed=%d suspended=%d courses_found=%d courses_closed=%d courses_hide_found=%d courses_hidden=%d reactivation_processed=%d courses_reactivation_found=%d courses_reactivated=%d errors=%d dry_run=%s',
    $processed,
    $suspended,
    $coursesFound,
    $coursesClosed,
    $coursesHideFound,
    $coursesHidden,
    $reactivationProcessed,
    $coursesReactivationFound,
    $coursesReactivated,
    $errors,
    $isDryRun ? 'true' : 'false'
);

if ($isCli) {
    echo $summary.PHP_EOL;
    exit(0 === $errors ? 0 : 1);
}

error_log($summary);
Display::addFlash(
    Display::return_message($summary, 0 === $errors ? 'success' : 'warning', false)
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_panel.php');
exit;
