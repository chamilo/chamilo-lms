<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$saleId = isset($_GET['sale_id']) && is_scalar($_GET['sale_id']) ? (int) $_GET['sale_id'] : 0;
$sale = $saleId > 0 ? $plugin->getSale($saleId) : null;

if (
    'true' !== $plugin->get('btcpay_enable')
    || empty($sale)
    || (int) $sale['user_id'] !== (int) api_get_user_id()
    || BuyCoursesPlugin::PAYMENT_TYPE_BTCPAY !== (int) $sale['payment_type']
) {
    api_not_allowed(true);
}

if (BuyCoursesPlugin::SALE_STATUS_COMPLETED === (int) $sale['status']) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('BtcPayCompletedMessage'), 'success', false)
    );
    unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);
} else {
    Display::addFlash(
        Display::return_message($plugin->get_lang('BtcPayPendingMessage'), 'info', false)
    );
}

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/course_catalog.php');
exit;
