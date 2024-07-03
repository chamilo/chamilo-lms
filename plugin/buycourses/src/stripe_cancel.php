<?php
/* For license terms, see /license.txt */

/**
 * Success page for the purchase of a course in the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$stripeEnabled = $plugin->get('stripe_enable') === 'true';

if (!$stripeEnabled) {
    api_not_allowed(true);
}

$sale = $plugin->getSale($_SESSION['bc_sale_id']);

if (empty($sale)) {
    api_not_allowed(true);
}

Display::addFlash(
    Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
);

unset($_SESSION['bc_sale_id']);
header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/course_catalog.php');
exit;
