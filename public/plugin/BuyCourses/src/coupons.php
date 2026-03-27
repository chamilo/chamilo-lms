<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * List of coupons of the Buy Courses plugin.
 */
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

if (isset($_GET['coupon_id'])) {
    $couponId = (int) $_GET['coupon_id'];
    $coupon = $plugin->getCouponInfo($couponId);

    if (empty($coupon)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self();

    switch ($_GET['action'] ?? '') {
        case 'deactivate':
            // Activate coupon.
            break;

        case 'activate':
            // Deactivate coupon.
            break;
    }

    header("Location: $urlToRedirect");
    exit;
}

$discountTypes = $plugin->getCouponDiscountTypes();
$couponStatuses = $plugin->getCouponStatuses();

$selectedStatus = isset($_GET['status'])
    ? (int) $_GET['status']
    : BuyCoursesPlugin::COUPON_STATUS_ACTIVE;

if (!array_key_exists($selectedStatus, $couponStatuses)) {
    $selectedStatus = BuyCoursesPlugin::COUPON_STATUS_ACTIVE;
}

$currency = $plugin->getSelectedCurrency();
$currencyIso = $currency['iso_code'] ?? null;

$coupons = $plugin->getCouponsListByStatus($selectedStatus);

foreach ($coupons as &$coupon) {
    if (BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE == (int) $coupon['discount_type']) {
        $coupon['discount_value'] = $coupon['discount_amount'].' %';
    } elseif (BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_AMOUNT == (int) $coupon['discount_type']) {
        if ($currencyIso) {
            $coupon['discount_value'] = $plugin->getPriceWithCurrencyFromIsoCode(
                (float) $coupon['discount_amount'],
                $currencyIso
            );
        } else {
            $coupon['discount_value'] = (string) $coupon['discount_amount'];
        }
    } else {
        $coupon['discount_value'] = (string) $coupon['discount_amount'];
    }

    $coupon['discount_type_label'] = $discountTypes[$coupon['discount_type']] ?? (string) $coupon['discount_type'];
}
unset($coupon);

$defaultBackUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php';
$backUrl = $defaultBackUrl;
$interbreadcrumb[] = [
    'url' => '../index.php',
    'name' => $plugin->get_lang('plugin_title'),
];

$templateName = $plugin->get_lang('CouponList');
$template = new Template($templateName);

$template->assign('page_title', $templateName);
$template->assign('plugin_title', $plugin->get_lang('plugin_title'));
$template->assign('back_url', $backUrl);
$template->assign('new_coupon_url', api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/coupon_add.php');

$template->assign('coupon_statuses', $couponStatuses);
$template->assign('selected_status', $selectedStatus);
$template->assign('coupon_list', $coupons);

$template->assign('coupon_status_active', BuyCoursesPlugin::COUPON_STATUS_ACTIVE);
$template->assign('coupon_status_disable', BuyCoursesPlugin::COUPON_STATUS_DISABLE);

$content = $template->fetch('BuyCourses/view/coupons.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
