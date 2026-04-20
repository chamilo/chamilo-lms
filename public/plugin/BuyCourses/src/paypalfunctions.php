<?php

declare(strict_types=1);
/*
 * PayPal API Module
 *
 * Defines all the global variables and the wrapper functions
 */

$PROXY_HOST = '127.0.0.1';
$PROXY_PORT = '808';

/*
 * Prefer the English variable name.
 * Keep legacy fallbacks to avoid breaking older callers.
 */
$SandboxFlag = $isSandbox ?? $test ?? $pruebas ?? false;
$API_UserName = $paypalUsername ?? '';
$API_Password = $paypalPassword ?? '';
$API_Signature = $paypalSignature ?? '';

// BN Code is only applicable for partners
$sBNCode = 'PP-ECWizard';

if (true == $SandboxFlag) {
    $API_Endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
    $PAYPAL_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=';
} else {
    $API_Endpoint = 'https://api-3t.paypal.com/nvp';
    $PAYPAL_URL = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=';
}

$USE_PROXY = false;
$version = '93';

if ('' == session_id()) {
    session_start();
}

/**
 * Prepare the parameters for the SetExpressCheckout API call.
 */
function CallShortcutExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $extra)
{
    $formattedAmount = number_format((float) $paymentAmount, 2, '.', '');

    $nvpstr = '&PAYMENTREQUEST_0_AMT='.urlencode($formattedAmount);
    $nvpstr .= '&PAYMENTREQUEST_0_ITEMAMT='.urlencode($formattedAmount);
    $nvpstr .= '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode((string) $paymentType);
    $nvpstr .= '&RETURNURL='.urlencode((string) $returnURL);
    $nvpstr .= '&CANCELURL='.urlencode((string) $cancelURL);
    $nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode((string) $currencyCodeType);
    $nvpstr .= $extra;

    $_SESSION['currencyCodeType'] = (string) $currencyCodeType;
    $_SESSION['PaymentType'] = (string) $paymentType;

    $resArray = hash_call('SetExpressCheckout', $nvpstr);
    $ack = strtoupper((string) ($resArray['ACK'] ?? ''));

    if ('SUCCESS' === $ack || 'SUCCESSWITHWARNING' === $ack) {
        $token = urldecode((string) ($resArray['TOKEN'] ?? ''));
        $_SESSION['TOKEN'] = $token;
    }

    return $resArray;
}

/**
 * Prepare the parameters for the MarkExpressCheckout API call.
 */
function CallMarkExpressCheckout(
    $paymentAmount,
    $currencyCodeType,
    $paymentType,
    $returnURL,
    $cancelURL,
    $shipToName,
    $shipToStreet,
    $shipToCity,
    $shipToState,
    $shipToCountryCode,
    $shipToZip,
    $shipToStreet2,
    $phoneNum
) {
    $formattedAmount = number_format((float) $paymentAmount, 2, '.', '');

    $nvpstr = '&PAYMENTREQUEST_0_AMT='.urlencode($formattedAmount);
    $nvpstr .= '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode((string) $paymentType);
    $nvpstr .= '&RETURNURL='.urlencode((string) $returnURL);
    $nvpstr .= '&CANCELURL='.urlencode((string) $cancelURL);
    $nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode((string) $currencyCodeType);
    $nvpstr .= '&ADDROVERRIDE=1';
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTONAME='.urlencode((string) $shipToName);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOSTREET='.urlencode((string) $shipToStreet);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOSTREET2='.urlencode((string) $shipToStreet2);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOCITY='.urlencode((string) $shipToCity);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOSTATE='.urlencode((string) $shipToState);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE='.urlencode((string) $shipToCountryCode);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOZIP='.urlencode((string) $shipToZip);
    $nvpstr .= '&PAYMENTREQUEST_0_SHIPTOPHONENUM='.urlencode((string) $phoneNum);

    $_SESSION['currencyCodeType'] = (string) $currencyCodeType;
    $_SESSION['PaymentType'] = (string) $paymentType;

    $resArray = hash_call('SetExpressCheckout', $nvpstr);
    $ack = strtoupper((string) ($resArray['ACK'] ?? ''));

    if ('SUCCESS' === $ack || 'SUCCESSWITHWARNING' === $ack) {
        $token = urldecode((string) ($resArray['TOKEN'] ?? ''));
        $_SESSION['TOKEN'] = $token;
    }

    return $resArray;
}

/**
 * Prepare the parameters for the GetExpressCheckoutDetails API call.
 */
function GetShippingDetails($token)
{
    $nvpstr = '&TOKEN='.urlencode((string) $token);

    $resArray = hash_call('GetExpressCheckoutDetails', $nvpstr);
    $ack = strtoupper((string) ($resArray['ACK'] ?? ''));

    if ('SUCCESS' === $ack || 'SUCCESSWITHWARNING' === $ack) {
        $_SESSION['payer_id'] = (string) ($resArray['PAYERID'] ?? '');
    }

    return $resArray;
}

/**
 * Finalize the PayPal payment.
 */
function ConfirmPayment($finalPaymentAmt)
{
    $token = (string) ($_SESSION['TOKEN'] ?? '');
    $paymentType = (string) ($_SESSION['PaymentType'] ?? 'Sale');
    $currencyCodeType = (string) ($_SESSION['currencyCodeType'] ?? '');
    $payerId = (string) ($_SESSION['payer_id'] ?? '');
    $clientIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    if ('' === $token || '' === $payerId || '' === $currencyCodeType) {
        return [
            'ACK' => 'FAILURE',
            'L_ERRORCODE0' => 'LOCAL1000',
            'L_LONGMESSAGE0' => 'Missing token, payer ID, or currency code for DoExpressCheckoutPayment.',
        ];
    }

    $formattedAmount = number_format((float) $finalPaymentAmt, 2, '.', '');

    $nvpstr = '&TOKEN='.urlencode($token);
    $nvpstr .= '&PAYERID='.urlencode($payerId);
    $nvpstr .= '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode($paymentType);
    $nvpstr .= '&PAYMENTREQUEST_0_AMT='.urlencode($formattedAmount);
    $nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($currencyCodeType);
    $nvpstr .= '&IPADDRESS='.urlencode($clientIp);

    return hash_call('DoExpressCheckoutPayment', $nvpstr);
}

/**
 * Make a DoDirectPayment API call.
 */
function DirectPayment(
    $paymentType,
    $paymentAmount,
    $creditCardType,
    $creditCardNumber,
    $expDate,
    $cvv2,
    $firstName,
    $lastName,
    $street,
    $city,
    $state,
    $zip,
    $countryCode,
    $currencyCode
) {
    $nvpstr = '&AMT='.urlencode((string) $paymentAmount);
    $nvpstr .= '&CURRENCYCODE='.urlencode((string) $currencyCode);
    $nvpstr .= '&PAYMENTACTION='.urlencode((string) $paymentType);
    $nvpstr .= '&CREDITCARDTYPE='.urlencode((string) $creditCardType);
    $nvpstr .= '&ACCT='.urlencode((string) $creditCardNumber);
    $nvpstr .= '&EXPDATE='.urlencode((string) $expDate);
    $nvpstr .= '&CVV2='.urlencode((string) $cvv2);
    $nvpstr .= '&FIRSTNAME='.urlencode((string) $firstName);
    $nvpstr .= '&LASTNAME='.urlencode((string) $lastName);
    $nvpstr .= '&STREET='.urlencode((string) $street);
    $nvpstr .= '&CITY='.urlencode((string) $city);
    $nvpstr .= '&STATE='.urlencode((string) $state);
    $nvpstr .= '&COUNTRYCODE='.urlencode((string) $countryCode);
    $nvpstr .= '&IPADDRESS='.urlencode((string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

    return hash_call('DoDirectPayment', $nvpstr);
}

/**
 * Make a MassPay API call.
 */
function MassPayment(array $beneficiaries, $currencyCode)
{
    $nvpstr = '&RECEIVERTYPE=EmailAddress';
    $nvpstr .= '&CURRENCYCODE='.urlencode((string) $currencyCode);

    $index = 0;

    foreach ($beneficiaries as $beneficiary) {
        $nvpstr .= '&L_EMAIL'.$index.'='.urlencode((string) $beneficiary['paypal_account']);
        $nvpstr .= '&L_AMT'.$index.'='.urlencode((string) $beneficiary['commission']);
        $index++;
    }

    return hash_call('MassPay', $nvpstr);
}

/**
 * Perform the API call to PayPal using API signature.
 */
function hash_call($methodName, $nvpStr)
{
    global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
    global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $sBNCode;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    if ($USE_PROXY) {
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST.':'.$PROXY_PORT);
    }

    $nvpreq = 'METHOD='.urlencode((string) $methodName)
        .'&VERSION='.urlencode((string) $version)
        .'&USER='.urlencode((string) $API_UserName)
        .'&PWD='.urlencode((string) $API_Password)
        .'&SIGNATURE='.urlencode((string) $API_Signature)
        .$nvpStr
        .'&BUTTONSOURCE='.urlencode((string) $sBNCode);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    $response = curl_exec($ch);

    if (false === $response) {
        $curlErrorNo = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
        curl_close($ch);

        return [
            'ACK' => 'FAILURE',
            'L_ERRORCODE0' => 'CURL'.$curlErrorNo,
            'L_LONGMESSAGE0' => $curlErrorMessage,
        ];
    }

    curl_close($ch);

    $nvpResArray = deformatNVP((string) $response);
    $nvpReqArray = deformatNVP($nvpreq);

    $_SESSION['nvpReqArray'] = $nvpReqArray;

    return $nvpResArray;
}

/**
 * Redirect to PayPal.
 */
function RedirectToPayPal($token): void
{
    global $PAYPAL_URL;

    header('Location: '.$PAYPAL_URL.$token);
    exit;
}

/**
 * Convert NVP string to associative array.
 */
function deformatNVP($nvpstr)
{
    $initial = 0;
    $nvpArray = [];

    while (strlen((string) $nvpstr)) {
        $keypos = strpos($nvpstr, '=');
        $valuepos = strpos($nvpstr, '&') ?: strlen($nvpstr);

        $keyval = substr($nvpstr, $initial, $keypos);
        $valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);

        $nvpArray[urldecode($keyval)] = urldecode($valval);
        $nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
    }

    return $nvpArray;
}
