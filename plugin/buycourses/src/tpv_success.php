<?php
/* For license terms, see /license.txt */

/**
 * Success page for the purchase of a course in the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$tpvRedsysEnabled = $plugin->get('tpv_redsys_enable') === 'true';

if (!$tpvRedsysEnabled) {
    api_not_allowed(true);
}

$sale = $plugin->getSale($_SESSION['bc_sale_id']);

if (empty($sale)) {
    api_not_allowed(true);
}

Display::addFlash(
    $plugin->getSubscriptionSuccessMessage($sale)
);
//$plugin->storePayouts($sale['id']);

unset($_SESSION['bc_sale_id']);
header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/course_catalog.php');
exit;
