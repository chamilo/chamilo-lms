<?php
/* For license terms, see /license.txt */
/**
 * Errors management for the Buy Courses plugin - Redirects to service_catalog.php with a error msg.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

if (isset($_SESSION['bc_service_sale_id'])) {
    $plugin = BuyCoursesPlugin::create();
    $serviceSaleId = $_SESSION['bc_service_sale_id'];
    unset($_SESSION['bc_service_sale_id']);
    $serviceSale = $plugin->getServiceSale($serviceSaleId);

    $plugin->cancelServiceSale(intval($serviceSaleId));
    Display::addFlash(
        Display::return_message(
            $plugin->get_lang('OrderCancelled'),
            'error',
            false
        )
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');
    exit;
}

Display::addFlash(
    Display::return_message($plugin->get_lang('ErrorOccurred'), 'error', false)
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/service_catalog.php');

exit;
