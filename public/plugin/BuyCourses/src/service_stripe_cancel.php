<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';
require_once __DIR__.'/admin_token.lib.php';

$plugin = BuyCoursesPlugin::create();
$serviceSaleId = isset($_GET['service_sale_id']) ? (int) $_GET['service_sale_id'] : 0;

// The id comes from the query string: only the buyer may cancel their own sale (anyone, even anonymous,
// could cancel any pending service sale before).
if ($serviceSaleId > 0 && api_get_user_id() > 0) {
    if (buycoursesUserOwnsServiceSale($plugin->getServiceSale($serviceSaleId), api_get_user_id())) {
        $plugin->cancelServiceSale($serviceSaleId);
    }
}

unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

Display::addFlash(
    Display::return_message(
        $plugin->get_lang('OrderCancelled'),
        'warning',
        false
    )
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php');
exit;
