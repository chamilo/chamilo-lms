<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$serviceSaleId = isset($_GET['service_sale_id']) ? (int) $_GET['service_sale_id'] : 0;

if ($serviceSaleId > 0) {
    $plugin->cancelServiceSale($serviceSaleId);
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
