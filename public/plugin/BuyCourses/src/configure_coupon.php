<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Configure an existing coupon with a clean Tailwind form contract.
 */
require_once '../config.php';

api_protect_admin_script();

$rawCouponId = $_REQUEST['id'] ?? 0;
$couponId = is_scalar($rawCouponId) ? (int) $rawCouponId : 0;

if ($couponId <= 0) {
    api_not_allowed();
}

$plugin = BuyCoursesPlugin::create();
$coupon = $plugin->getCouponInfo($couponId);

if (empty($coupon)) {
    api_not_allowed();
}

$includeSession = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

$currency = $plugin->getSelectedCurrency();
$currencyIso = $currency['iso_code'] ?? '';

/**
 * Normalize datetime-local input to database format.
 */
function normalizeConfiguredCouponDateTime(?string $value): ?string
{
    if (null === $value || '' === trim($value)) {
        return null;
    }

    $value = trim($value);

    $formats = [
        'Y-m-d\TH:i',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
    ];

    foreach ($formats as $format) {
        $dateTime = DateTime::createFromFormat($format, $value);

        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }

    try {
        $dateTime = new DateTime($value);

        return $dateTime->format('Y-m-d H:i:s');
    } catch (Throwable $exception) {
        return null;
    }
}

/**
 * Format a database datetime for datetime-local inputs.
 */
function formatConfiguredCouponDateTimeInput(?string $value): string
{
    if (null === $value || '' === trim($value)) {
        return '';
    }

    try {
        return (new DateTime($value))->format('Y-m-d\TH:i');
    } catch (Throwable $exception) {
        return '';
    }
}

/**
 * Sanitize selected ids using available options.
 *
 * @param mixed $values
 */
function sanitizeConfiguredCouponSelectedIds($values, array $available): array
{
    $values = is_array($values) ? $values : [];

    return array_values(
        array_unique(
            array_filter(
                array_map('intval', $values),
                static fn (int $id): bool => $id > 0 && isset($available[$id])
            )
        )
    );
}

/**
 * Build a lookup map for Twig.
 */
function buildConfiguredCouponLookup(array $ids): array
{
    $lookup = [];

    foreach ($ids as $id) {
        $lookup[(int) $id] = true;
    }

    return $lookup;
}

$courses = [];
$sessions = [];
$services = [];
$messages = [];

if (empty($currency)) {
    $messages[] = Display::return_message(
        $plugin->get_lang('CurrencyIsNotConfigured'),
        'error',
        false
    );
}

$coursesList = CourseManager::get_courses_list(
    0,
    0,
    'title',
    'asc',
    -1,
    null,
    api_get_current_access_url_id(),
    false,
    [],
    []
);

foreach ($coursesList as $course) {
    $courseId = isset($course['id']) ? (int) $course['id'] : 0;
    $courseTitle = isset($course['title']) ? trim((string) $course['title']) : '';

    if ($courseId <= 0 || '' === $courseTitle) {
        continue;
    }

    $courses[$courseId] = $courseTitle;
}

$sessionsList = SessionManager::get_sessions_list(
    [],
    [],
    null,
    null,
    api_get_current_access_url_id(),
    []
);

foreach ($sessionsList as $session) {
    $sessionId = isset($session['id']) ? (int) $session['id'] : 0;

    $sessionName = '';
    if (isset($session['name']) && '' !== trim((string) $session['name'])) {
        $sessionName = trim((string) $session['name']);
    } elseif (isset($session['session_name']) && '' !== trim((string) $session['session_name'])) {
        $sessionName = trim((string) $session['session_name']);
    } elseif (isset($session['title']) && '' !== trim((string) $session['title'])) {
        $sessionName = trim((string) $session['title']);
    } elseif (isset($session['name_and_dates']) && '' !== trim((string) $session['name_and_dates'])) {
        $sessionName = trim((string) $session['name_and_dates']);
    }

    if ($sessionId <= 0 || '' === $sessionName) {
        continue;
    }

    $sessions[$sessionId] = $sessionName;
}

$servicesList = $plugin->getAllServices();

foreach ($servicesList as $service) {
    $serviceId = isset($service['id']) ? (int) $service['id'] : 0;
    $serviceName = isset($service['name']) ? trim((string) $service['name']) : '';

    if ($serviceId <= 0 || '' === $serviceName) {
        continue;
    }

    $services[$serviceId] = $serviceName;
}

$discountTypes = $plugin->getCouponDiscountTypes();

$coursesAdded = !empty($coupon['courses'])
    ? array_values(array_map('intval', array_column($coupon['courses'], 'id')))
    : [];

$sessionsAdded = !empty($coupon['sessions'])
    ? array_values(array_map('intval', array_column($coupon['sessions'], 'id')))
    : [];

$servicesAdded = !empty($coupon['services'])
    ? array_values(array_map('intval', array_column($coupon['services'], 'id')))
    : [];

$formData = [
    'id' => $couponId,
    'code' => (string) ($coupon['code'] ?? ''),
    'discount_type' => (string) ((int) ($coupon['discount_type'] ?? 0)),
    'discount_type_label' => (string) ($discountTypes[(int) ($coupon['discount_type'] ?? 0)] ?? ''),
    'discount_amount' => (string) ($coupon['discount_amount'] ?? '0'),
    'date_start_input' => formatConfiguredCouponDateTimeInput((string) ($coupon['valid_start'] ?? '')),
    'date_end_input' => formatConfiguredCouponDateTimeInput((string) ($coupon['valid_end'] ?? '')),
    'active' => !empty($coupon['active']),
    'courses' => sanitizeConfiguredCouponSelectedIds($coursesAdded, $courses),
    'courses_lookup' => [],
    'sessions' => sanitizeConfiguredCouponSelectedIds($sessionsAdded, $sessions),
    'sessions_lookup' => [],
    'services' => sanitizeConfiguredCouponSelectedIds($servicesAdded, $services),
    'services_lookup' => [],
];

$formData['courses_lookup'] = buildConfiguredCouponLookup($formData['courses']);
$formData['sessions_lookup'] = buildConfiguredCouponLookup($formData['sessions']);
$formData['services_lookup'] = buildConfiguredCouponLookup($formData['services']);

$csrfSessionKey = 'buycourses_configure_coupon_token_'.$couponId;

if (empty($_SESSION[$csrfSessionKey])) {
    $_SESSION[$csrfSessionKey] = bin2hex(random_bytes(32));
}

$csrfToken = (string) $_SESSION[$csrfSessionKey];

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $formData['active'] = isset($_POST['active']);
    $formData['date_start_input'] = trim((string) ($_POST['date_start'] ?? $formData['date_start_input']));
    $formData['date_end_input'] = trim((string) ($_POST['date_end'] ?? $formData['date_end_input']));
    $formData['courses'] = sanitizeConfiguredCouponSelectedIds($_POST['courses'] ?? [], $courses);
    $formData['sessions'] = sanitizeConfiguredCouponSelectedIds($_POST['sessions'] ?? [], $sessions);
    $formData['services'] = sanitizeConfiguredCouponSelectedIds($_POST['services'] ?? [], $services);

    $formData['courses_lookup'] = buildConfiguredCouponLookup($formData['courses']);
    $formData['sessions_lookup'] = buildConfiguredCouponLookup($formData['sessions']);
    $formData['services_lookup'] = buildConfiguredCouponLookup($formData['services']);

    $hasError = false;

    $submittedToken = (string) ($_POST['csrf_token'] ?? '');
    if ('' === $submittedToken || !hash_equals($csrfToken, $submittedToken)) {
        $messages[] = Display::return_message(
            'Invalid form token. Please refresh the page and try again.',
            'error',
            false
        );
        $hasError = true;
    }

    $submittedId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($submittedId !== $couponId) {
        $messages[] = Display::return_message(
            'Invalid coupon identifier.',
            'error',
            false
        );
        $hasError = true;
    }

    $validStart = normalizeConfiguredCouponDateTime($formData['date_start_input']);
    $validEnd = normalizeConfiguredCouponDateTime($formData['date_end_input']);

    if (null === $validStart || null === $validEnd) {
        $messages[] = Display::return_message(
            'Both start and end dates are required.',
            'error',
            false
        );
        $hasError = true;
    } elseif ($validStart > $validEnd) {
        $messages[] = Display::return_message(
            'The start date cannot be later than the end date.',
            'error',
            false
        );
        $hasError = true;
    }

    if (empty($currency)) {
        $hasError = true;
    }

    if (!$hasError) {
        $couponToUpdate = $coupon;
        $couponToUpdate['id'] = $couponId;
        $couponToUpdate['valid_start'] = (string) $validStart;
        $couponToUpdate['valid_end'] = (string) $validEnd;
        $couponToUpdate['active'] = $formData['active'] ? 1 : 0;
        $couponToUpdate['courses'] = $formData['courses'];
        $couponToUpdate['sessions'] = $formData['sessions'];
        $couponToUpdate['services'] = $formData['services'];

        $result = $plugin->updateCouponData($couponToUpdate);

        if ($result) {
            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('CouponUpdate'),
                    'success',
                    false
                )
            );

            $_SESSION[$csrfSessionKey] = bin2hex(random_bytes(32));
            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/configure_coupon.php?id='.$couponId);
            exit;
        }

        $messages[] = Display::return_message(
            $plugin->get_lang('ErrorContactPlatformAdmin'),
            'error',
            false
        );
    }
}

$templateName = $plugin->get_lang('ConfigureCoupon');

$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => 'coupons.php',
    'name' => $plugin->get_lang('CouponList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/coupons.php');
$template->assign('messages', $messages);
$template->assign('csrf_token', $csrfToken);
$template->assign('currency_iso', $currencyIso);
$template->assign('discount_types', $discountTypes);
$template->assign('include_sessions', $includeSession);
$template->assign('include_services', $includeServices);
$template->assign('courses_options', $courses);
$template->assign('sessions_options', $sessions);
$template->assign('services_options', $services);
$template->assign('form_data', $formData);
$template->assign('submit_disabled', empty($currency));
$template->assign('form_mode', 'configure');
$template->assign('action_url', api_get_self().'?id='.$couponId);
$template->assign('read_only_code', true);
$template->assign('read_only_discount_type', true);
$template->assign('read_only_discount_amount', true);
$template->assign('submit_label', get_lang('Save'));

$content = $template->fetch('BuyCourses/view/coupon_add.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
