<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Buyer-only link that resumes a pending purchase and applies a reminder coupon.
 */
use Chamilo\CoreBundle\Helpers\BuyCoursesPurchaseReminderHelper;
use ChamiloSession as Session;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$helper = $plugin->getPurchaseReminderHelper();

$saleKind = (string) ($_GET['k'] ?? '');
$saleId = isset($_GET['s']) ? (int) $_GET['s'] : 0;
$couponId = isset($_GET['c']) ? (int) $_GET['c'] : 0;

$currentUserId = api_get_user_id();
$query = http_build_query(array_filter([
    'k' => $saleKind,
    's' => $saleId,
    'c' => $couponId > 0 ? $couponId : null,
]));
$currentPath = '/plugin/BuyCourses/src/purchase_reminder_link.php?'.$query;

if ($currentUserId <= 0 || api_is_anonymous()) {
    Session::write('buy_course_redirect', $currentPath);
    header('Location: '.api_get_path(WEB_CODE_PATH).'auth/registration.php');
    exit;
}

$result = $helper->resumePurchaseForUser(
    $currentUserId,
    $saleKind,
    $saleId,
    $couponId > 0 ? $couponId : null
);

if (!$result['ok']) {
    if ('forbidden' === $result['error']) {
        api_not_allowed(true);
    }

    $message = match ($result['error']) {
        'not_pending' => $plugin->get_lang('PurchaseReminderSaleNoLongerPending'),
        default => $plugin->get_lang('PurchaseReminderLinkInvalid'),
    };
    Display::addFlash(Display::return_message($message, 'warning', false));
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php');
    exit;
}

if ('coupon' === $result['error']) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('PurchaseReminderCouponUnavailable'), 'warning', false)
    );
}

if (BuyCoursesPurchaseReminderHelper::SALE_KIND_SERVICE === $saleKind) {
    Session::write('bc_service_sale_id', $saleId);
} else {
    $_SESSION['bc_sale_id'] = $saleId;
}

if (!empty($result['coupon_id'])) {
    $_SESSION['bc_coupon_id'] = (int) $result['coupon_id'];
}

header('Location: '.$result['redirect']);
exit;
