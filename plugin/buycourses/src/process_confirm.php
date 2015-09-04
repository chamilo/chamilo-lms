<?php
/* For license terms, see /license.txt */
/**
 * Process purchase confirmation script for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Init
 */
require_once '../config.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';

$plugin = BuyCoursesPlugin::create();

$saleId = $_SESSION['bc_sale_id'];

if (empty($saleId)) {
    api_not_allowed(true);
}

$sale = $plugin->getSale($saleId);

if (empty($sale)) {
    api_not_allowed(true);
}

$currency = $plugin->getCurrency($sale['currency_id']);

switch ($sale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $pruebas = $paypalParams['sandbox'] == 1;
        $paypalUsername = $paypalParams['username'];
        $paypalPassword = $paypalParams['password'];
        $paypalSignature = $paypalParams['signature'];

        require_once("paypalfunctions.php");

        $i = 0;
        $extra = "&L_PAYMENTREQUEST_0_NAME0={$sale['product_name']}";
        $extra .= "&L_PAYMENTREQUEST_0_AMT0={$sale['price']}";
        $extra .= "&L_PAYMENTREQUEST_0_QTY0=1";

        $expressCheckout = CallShortcutExpressCheckout(
            $sale['price'],
            $currency['iso_code'],
            'paypal',
            api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/success.php',
            api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/error.php',
            $extra
        );

        if ($expressCheckout["ACK"] !== 'Success') {
            var_dump([
                'error_code' => $expressCheckout['L_ERRORCODE0'],
                'short_message' => $expressCheckout['L_SHORTMESSAGE0'],
                'long_message' => $expressCheckout['L_LONGMESSAGE0'],
                'severity_code' => $expressCheckout['L_SEVERITYCODE0']
            ]);
            exit;
        }

        RedirectToPayPal($expressCheckout["TOKEN"]);
        break;
    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
        break;
}
