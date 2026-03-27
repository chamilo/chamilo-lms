<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();

$saleId = (int) ($_SESSION['bc_sale_id'] ?? 0);
$redirectUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_course_catalog.php';

if ($saleId > 0) {
    $sale = $plugin->getSubscriptionSale($saleId);

    if (!empty($sale)) {
        if (BuyCoursesPlugin::PRODUCT_TYPE_SESSION === (int) $sale['product_type']) {
            $redirectUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_session_catalog.php';
        }

        $plugin->cancelSubscriptionSale($saleId);
    }
}

unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);

Display::addFlash(
    Display::return_message($plugin->get_lang('OrderCancelled'), 'warning', false)
);

header('Location: '.$redirectUrl);
exit;
