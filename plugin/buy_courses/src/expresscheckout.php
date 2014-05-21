<?php

require_once("paypalfunctions.php");
// ==================================
// PayPal Express Checkout Module
// ==================================

//'------------------------------------
//' The paymentAmount is the total value of 
//' the shopping cart, that was set 
//' earlier in a session variable 
//' by the shopping cart page
//'------------------------------------
$paymentAmount = $_SESSION["Payment_Amount"];

//'------------------------------------
//' The currencyCodeType and paymentType 
//' are set to the selections made on the Integration Assistant 
//'------------------------------------
$currencyCodeType = "EUR";
$paymentType = "Sale";

//'------------------------------------
//' The returnURL is the location where buyers return to when a
//' payment has been succesfully authorized.
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------
$returnURL = "http://www.nosolored.me/pluging/paypal/confirm.php";

//'------------------------------------
//' The cancelURL is the location buyers are sent to when they hit the
//' cancel button during authorization of payment during the PayPal flow
//'
//' This is set to the value entered on the Integration Assistant 
//'------------------------------------
$cancelURL = "http://www.nosolored.me/pluging/paypal/error.php";

//'------------------------------------
//' Calls the SetExpressCheckout API call
//'
//' The CallShortcutExpressCheckout function is defined in the file PayPalFunctions.php,
//' it is included at the top of this file.
//'-------------------------------------------------
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
    echo "Detailed Error Message: " . $ErrorLongMsg;
    echo "Short Error Message: " . $ErrorShortMsg;
    echo "Error Code: " . $ErrorCode;
    echo "Error Severity Code: " . $ErrorSeverityCode;
}
?>