<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Clean coupon add page for BuyCourses.
 * Uses a manual Tailwind form instead of legacy FormValidator HTML.
 */
require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$includeSession = 'true' === $plugin->get('include_sessions');
$includeServices = 'true' === $plugin->get('include_services');

$currency = $plugin->getSelectedCurrency();
$currencyIso = $currency['iso_code'] ?? '';

/**
 * Normalize datetime-local input to database format.
 */
function normalizeCouponDateTime(?string $value): ?string
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
 * Format date for datetime-local input.
 */
function formatCouponDateTimeInput(?string $value): string
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
function sanitizeCouponSelectedIds($values, array $available): array
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
function buildCouponLookup(array $ids): array
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

$defaultStart = new DateTime('today 00:00:00');
$defaultEnd = new DateTime('tomorrow 00:00:00');

$formData = [
    'code' => '',
    'discount_type' => '',
    'discount_amount' => '0',
    'date_start_input' => $defaultStart->format('Y-m-d\TH:i'),
    'date_end_input' => $defaultEnd->format('Y-m-d\TH:i'),
    'active' => false,
    'courses' => [],
    'courses_lookup' => [],
    'sessions' => [],
    'sessions_lookup' => [],
    'services' => [],
    'services_lookup' => [],
];

$csrfSessionKey = 'buycourses_coupon_add_token';

if (empty($_SESSION[$csrfSessionKey])) {
    $_SESSION[$csrfSessionKey] = bin2hex(random_bytes(32));
}

$csrfToken = (string) $_SESSION[$csrfSessionKey];

if (empty($currency)) {
    $messages[] = Display::return_message(
        $plugin->get_lang('CurrencyIsNotConfigured'),
        'error',
        false
    );
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $formData['code'] = trim((string) ($_POST['code'] ?? ''));
    $formData['discount_type'] = trim((string) ($_POST['discount_type'] ?? ''));
    $formData['discount_amount'] = trim((string) ($_POST['discount_amount'] ?? '0'));
    $formData['date_start_input'] = trim((string) ($_POST['date_start'] ?? $formData['date_start_input']));
    $formData['date_end_input'] = trim((string) ($_POST['date_end'] ?? $formData['date_end_input']));
    $formData['active'] = isset($_POST['active']);

    $formData['courses'] = sanitizeCouponSelectedIds($_POST['courses'] ?? [], $courses);
    $formData['sessions'] = sanitizeCouponSelectedIds($_POST['sessions'] ?? [], $sessions);
    $formData['services'] = sanitizeCouponSelectedIds($_POST['services'] ?? [], $services);

    $formData['courses_lookup'] = buildCouponLookup($formData['courses']);
    $formData['sessions_lookup'] = buildCouponLookup($formData['sessions']);
    $formData['services_lookup'] = buildCouponLookup($formData['services']);

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

    if ('' === $formData['code']) {
        $messages[] = Display::return_message(
            'Coupon code is required.',
            'error',
            false
        );
        $hasError = true;
    }

    $discountType = (int) $formData['discount_type'];
    if (!array_key_exists($discountType, $discountTypes)) {
        $messages[] = Display::return_message(
            'Discount type is required.',
            'error',
            false
        );
        $hasError = true;
    }

    $discountAmount = (float) str_replace(',', '.', $formData['discount_amount']);
    if ($discountAmount < 0) {
        $messages[] = Display::return_message(
            'Discount must be zero or greater.',
            'error',
            false
        );
        $hasError = true;
    }

    $validStart = normalizeCouponDateTime($formData['date_start_input']);
    $validEnd = normalizeCouponDateTime($formData['date_end_input']);

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

    if (
        BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE === $discountType &&
        $discountAmount > 100
    ) {
        $messages[] = Display::return_message(
            $plugin->get_lang('CouponDiscountExceed100'),
            'error',
            false
        );
        $hasError = true;
    }

    if (empty($currency)) {
        $hasError = true;
    }

    if (!$hasError) {
        $coupon = [];
        $coupon['code'] = $formData['code'];
        $coupon['discount_type'] = $discountType;
        $coupon['discount_amount'] = $discountAmount;
        $coupon['valid_start'] = (string) $validStart;
        $coupon['valid_end'] = (string) $validEnd;
        $coupon['active'] = $formData['active'] ? 1 : 0;
        $coupon['courses'] = $formData['courses'];
        $coupon['sessions'] = $formData['sessions'];
        $coupon['services'] = $formData['services'];

        $result = $plugin->addNewCoupon($coupon);

        if ($result) {
            $_SESSION[$csrfSessionKey] = bin2hex(random_bytes(32));
            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/coupons.php');
            exit;
        }

        $messages[] = Display::return_message(
            'The coupon could not be saved.',
            'error',
            false
        );
    }
}

if (empty($formData['courses_lookup'])) {
    $formData['courses_lookup'] = buildCouponLookup($formData['courses']);
}

if (empty($formData['sessions_lookup'])) {
    $formData['sessions_lookup'] = buildCouponLookup($formData['sessions']);
}

if (empty($formData['services_lookup'])) {
    $formData['services_lookup'] = buildCouponLookup($formData['services']);
}

$templateName = $plugin->get_lang('CouponAdd');

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

$content = $template->fetch('BuyCourses/view/coupon_add.tpl');
$template->assign('content', $content);
$template->display_one_col_template();
