<?php
/* For license terms, see /license.txt */

/**
 * Success page for the purchase of a course in the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$cecabankEnabled = $plugin->get('cecabank_enable') === 'true';

if (!$cecabankEnabled) {
    api_not_allowed(true);
}

$receivedAmount = (float) $_POST['Importe'];

if (empty($_POST['Num_operacion']) || empty($_POST['Firma']) || empty($receivedAmount)) {
    api_not_allowed(true);
}

$signature = $plugin->getCecabankSignature($_POST['Num_operacion'], $receivedAmount);

if ($signature != $_POST['Firma']) {
    api_not_allowed(true);
}

$sale = $plugin->getSaleFromReference($_POST['Num_operacion']);

if (empty($sale)) {
    api_not_allowed(true);
}

$buyingCourse = false;
$buyingSession = false;

switch ($sale['product_type']) {
    case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
        $buyingCourse = true;
        $course = $plugin->getCourseInfo($sale['product_id']);
        break;
    case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
        $buyingSession = true;
        $session = $plugin->getSessionInfo($sale['product_id']);
        break;
}

$saleIsCompleted = $plugin->completeSale($sale['id']);
if ($saleIsCompleted) {
    $plugin->storePayouts($sale['id']);
}
