<?php
/* For license terms, see /license.txt */
/**
 * PayPal Express Checkout Module.
 *
 * @package chamilo.plugin.buycourses
 */
/**
 * Init.
 */
require_once 'paypalfunctions.php';
/**
 * The paymentAmount is the total value of
 * the shopping cart, that was set
 * earlier in a session variable
 * by the shopping cart page.
 */
$paymentAmount = $_SESSION["Payment_Amount"];

/**
 * The currencyCodeType and paymentType
 * are set to the selections made on the Integration Assistant.
 */
$paymentType = "Sale";

/**
 * Calls the SetExpressCheckout API call
 * The CallShortcutExpressCheckout function is defined in the file PayPalFunctions.php,
 * it is included at the top of this file.
 */
$resArray = CallShortcutExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL);
$ack = strtoupper($resArray["ACK"]);
if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
    RedirectToPayPal($resArray["TOKEN"]);
} else {
    //Display a user friendly Error on the page using any of the following error information returned by PayPal
    $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
    $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
    $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
    $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

    echo "SetExpressCheckout API call failed. ";
    echo "Detailed Error Message: ".$ErrorLongMsg;
    echo "Short Error Message: ".$ErrorShortMsg;
    echo "Error Code: ".$ErrorCode;
    echo "Error Severity Code: ".$ErrorSeverityCode;
}
