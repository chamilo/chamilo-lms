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

$tpvRedsysParams = $plugin->getTpvRedsysParams();

$version = $_POST['Ds_SignatureVersion'];
$params = $_POST["Ds_MerchantParameters"];
$signatureReceived = $_POST['Ds_Signature'];

require_once '../resources/apiRedsys.php';
$tpv = new RedsysAPI();

$decodec = $tpv->decodeMerchantParameters($params);
$kc = $tpvRedsysParams['kc'];
$signature = $tpv->createMerchantSignatureNotif($kc, $params);

if ($signature === $signatureReceived) {
    $saleId = (int) $tpv->getParameter("Ds_Order");
    $response = $tpv->getParameter("Ds_Response");

    // other fields available
    // $Ds_Amount=$miObj->getParameter("Ds_Amount");
    // $Ds_MerchantCode=$miObj->getParameter("Ds_MerchantCode");
    // $Ds_TransactionType=$miObj->getParameter("Ds_TransactionType");
    // $Ds_MerchantData=$miObj->getParameter("Ds_MerchantData");
    // $Ds_Date=$miObj->getParameter("Ds_Date");
    // $Ds_Hour=$miObj->getParameter("Ds_Hour");

    $sale = $plugin->getSale($saleId);
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

    if ($response == "0000") {
        $saleIsCompleted = $plugin->completeSale($sale['id']);
        if ($saleIsCompleted) {
            $plugin->storePayouts($sale['id']);
        }
    }
}
