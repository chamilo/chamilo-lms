<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Enable or cancel PayPal recurring billing for a completed renewable service sale.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$includeServices = 'true' === $plugin->get('include_services');
$paypalEnabled = 'true' === $plugin->get('paypal_enable');
$currentUserId = api_get_user_id();

if (!$includeServices || !$paypalEnabled || $currentUserId <= 0) {
    api_not_allowed(true);
}

$orderId = isset($_REQUEST['order']) ? (int) $_REQUEST['order'] : (int) Session::read('bc_recurring_service_sale_id');
$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : (string) Session::read('bc_recurring_action');
$token = isset($_REQUEST['token']) ? trim((string) $_REQUEST['token']) : '';
$panelUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_panel.php';

if ('disable_recurring_payment' === $action) {
    $action = 'cancel_recurring_payment';
}

if ($orderId <= 0 || '' === $action) {
    api_not_allowed(true);
}

$serviceSale = $plugin->getServiceSale($orderId);

if (empty($serviceSale) || (int) ($serviceSale['buyer']['id'] ?? 0) !== $currentUserId) {
    api_not_allowed(true);
}

if ((int) ($serviceSale['status'] ?? BuyCoursesPlugin::SERVICE_STATUS_PENDING) !== BuyCoursesPlugin::SERVICE_STATUS_COMPLETED) {
    Display::addFlash(
        Display::return_message('Recurring payment requires a completed sale.', 'warning', false)
    );

    header('Location: '.$panelUrl);
    exit;
}

if ((int) ($serviceSale['payment_type'] ?? 0) !== BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL) {
    Display::addFlash(
        Display::return_message('Recurring payment is only available for PayPal service sales.', 'warning', false)
    );

    header('Location: '.$panelUrl);
    exit;
}

if (empty($serviceSale['service']['renewable'])) {
    Display::addFlash(
        Display::return_message('This service is not renewable.', 'warning', false)
    );

    header('Location: '.$panelUrl);
    exit;
}

$paypalParams = $plugin->getPaypalParams();

$isSandbox = 1 === (int) ($paypalParams['sandbox'] ?? 0);
$test = $isSandbox;
$pruebas = $isSandbox;

$paypalUsername = trim((string) ($paypalParams['username'] ?? ''));
$paypalPassword = trim((string) ($paypalParams['password'] ?? ''));
$paypalSignature = trim((string) ($paypalParams['signature'] ?? ''));

error_log('[BuyCourses][Recurring] PayPal sandbox='.($isSandbox ? 'true' : 'false'));
error_log('[BuyCourses][Recurring] API username configured='.('' !== $paypalUsername ? 'yes' : 'no'));
error_log('[BuyCourses][Recurring] API password configured='.('' !== $paypalPassword ? 'yes' : 'no'));
error_log('[BuyCourses][Recurring] API signature configured='.('' !== $paypalSignature ? 'yes' : 'no'));

if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
    Display::addFlash(
        Display::return_message('PayPal API credentials are incomplete.', 'error', false)
    );

    header('Location: '.$panelUrl);
    exit;
}

require_once 'paypalfunctions.php';

$redirectWithError = static function (string $message) use ($panelUrl): void {
    Display::addFlash(Display::return_message($message, 'error', false));
    header('Location: '.$panelUrl);
    exit;
};

$redirectWithSuccess = static function (string $message) use ($panelUrl): void {
    Display::addFlash(Display::return_message($message, 'success', false));
    header('Location: '.$panelUrl);
    exit;
};

switch ($action) {
    case 'cancel_action':
        Session::erase('bc_recurring_service_sale_id');
        Session::erase('bc_recurring_action');

        Display::addFlash(
            Display::return_message($plugin->get_lang('CancelledAction'), 'warning', false)
        );

        header('Location: '.$panelUrl);
        exit;

    case 'enable_recurring_payment':
        if (BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED === (int) ($serviceSale['recurring_payment'] ?? 0)) {
            $redirectWithSuccess('Recurring payment is already enabled.');
        }

        if ('' === $token) {
            Session::write('bc_recurring_service_sale_id', $orderId);
            Session::write('bc_recurring_action', $action);

            $returnUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/recurring_payment_process.php?order='.$orderId.'&action=enable_recurring_payment';
            $cancelUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/recurring_payment_process.php?order='.$orderId.'&action=cancel_action';
            $description = (string) ($serviceSale['service']['name'] ?? 'Service');
            $currency = $plugin->getCurrency((int) ($serviceSale['currency_id'] ?? 0));
            $currencyCode = strtoupper(trim((string) ($currency['iso_code'] ?? ($serviceSale['service']['currency'] ?? ''))));

            $extra = '&PAYMENTREQUEST_0_AMT=0.00';
            $extra .= '&PAYMENTREQUEST_0_ITEMAMT=0.00';
            $extra .= '&PAYMENTREQUEST_0_PAYMENTACTION=Sale';
            $extra .= '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($currencyCode);
            $extra .= '&L_PAYMENTREQUEST_0_NAME0='.urlencode($description);
            $extra .= '&L_PAYMENTREQUEST_0_QTY0=1';
            $extra .= '&L_PAYMENTREQUEST_0_AMT0=0.00';
            $extra .= '&L_BILLINGTYPE0=RecurringPayments';
            $extra .= '&L_BILLINGAGREEMENTDESCRIPTION0='.urlencode($description);
            $extra .= '&RETURNURL='.urlencode($returnUrl);
            $extra .= '&CANCELURL='.urlencode($cancelUrl);

            $expressCheckout = MinimalExpressCheckout($extra);
            $ack = strtoupper((string) ($expressCheckout['ACK'] ?? ''));

            if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
                $errorCode = (string) ($expressCheckout['L_ERRORCODE0'] ?? 'unknown');
                $longMessage = (string) ($expressCheckout['L_LONGMESSAGE0'] ?? 'Unknown PayPal error.');

                error_log(
                    '[BuyCourses][Recurring] SetExpressCheckout failed for service sale '.$orderId.
                    ' CODE='.$errorCode.
                    ' MESSAGE='.$longMessage
                );

                $redirectWithError('PayPal error '.$errorCode.': '.$longMessage);
            }

            RedirectToPayPal((string) ($expressCheckout['TOKEN'] ?? ''));
            exit;
        }

        $shippingDetails = GetShippingDetails($token);
        $shippingAck = strtoupper((string) ($shippingDetails['ACK'] ?? ''));

        if (!in_array($shippingAck, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
            $errorCode = (string) ($shippingDetails['L_ERRORCODE0'] ?? 'unknown');
            $longMessage = (string) ($shippingDetails['L_LONGMESSAGE0'] ?? 'Unknown PayPal error.');

            error_log(
                '[BuyCourses][Recurring] GetExpressCheckoutDetails failed for service sale '.$orderId.
                ' CODE='.$errorCode.
                ' MESSAGE='.$longMessage
            );

            $redirectWithError('PayPal error '.$errorCode.': '.$longMessage);
        }

        $durationDays = max(1, (int) ($serviceSale['service']['duration_days'] ?? 1));
        $totalCharges = max(0, (int) ($serviceSale['service']['total_charges'] ?? 0));
        $currency = $plugin->getCurrency((int) ($serviceSale['currency_id'] ?? 0));
        $currencyCode = strtoupper(trim((string) ($currency['iso_code'] ?? ($serviceSale['service']['currency'] ?? ''))));
        $amount = (float) ($serviceSale['price'] ?? 0);
        $description = (string) ($serviceSale['service']['name'] ?? 'Service');
        $reference = (string) ($serviceSale['reference'] ?? 'service-sale-'.$orderId);
        $buyerName = (string) ($serviceSale['buyer']['name'] ?? '');
        $buyerEmail = (string) ($shippingDetails['EMAIL'] ?? '');

        $billingStart = new DateTimeImmutable((string) ($serviceSale['date_end'] ?? 'now'), new DateTimeZone('UTC'));

        if ($billingStart <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            $billingStart = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+1 hour');
        }

        $recurringProfile = CreateRecurringPaymentsProfile(
            $buyerName,
            $billingStart->format('Y-m-d\TH:i:s\Z'),
            $reference,
            $description,
            'Day',
            $durationDays,
            $totalCharges,
            $amount,
            $currencyCode,
            $buyerEmail,
            '',
            $token
        );

        $profileAck = strtoupper((string) ($recurringProfile['ACK'] ?? ''));

        if (!in_array($profileAck, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
            $errorCode = (string) ($recurringProfile['L_ERRORCODE0'] ?? 'unknown');
            $longMessage = (string) ($recurringProfile['L_LONGMESSAGE0'] ?? 'Unknown PayPal error.');

            error_log(
                '[BuyCourses][Recurring] CreateRecurringPaymentsProfile failed for service sale '.$orderId.
                ' CODE='.$errorCode.
                ' MESSAGE='.$longMessage
            );

            $redirectWithError('PayPal error '.$errorCode.': '.$longMessage);
        }

        $previousProfileId = trim((string) ($serviceSale['recurring_profile_id'] ?? ''));
        $newProfileId = (string) ($recurringProfile['PROFILEID'] ?? '');

        if ('' !== $previousProfileId && $previousProfileId !== $newProfileId) {
            ManageRecurringPaymentsProfileStatus(
                $previousProfileId,
                BuyCoursesPlugin::PAYPAL_RECURRING_PAYMENT_CANCEL,
                'Replaced by a new BuyCourses recurring profile.'
            );
        }

        $plugin->updateServiceSaleGatewayData(
            $orderId,
            [
                'recurring_gateway' => 'paypal',
                'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED,
                'recurring_profile_id' => $newProfileId,
                'next_charge_date' => $billingStart->format('Y-m-d H:i:s'),
            ]
        );

        Session::erase('bc_recurring_service_sale_id');
        Session::erase('bc_recurring_action');
        unset($_SESSION['TOKEN'], $_SESSION['payer_id']);

        $redirectWithSuccess('Recurring payment enabled successfully.');
        break;

    case 'cancel_recurring_payment':
        $profileId = trim((string) ($serviceSale['recurring_profile_id'] ?? ''));

        if ('' === $profileId) {
            $plugin->updateServiceSaleRecurringData(
                $orderId,
                BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED,
                null,
                null,
                api_get_utc_datetime()
            );

            $redirectWithSuccess('Recurring payment cancelled successfully.');
        }

        error_log('[BuyCourses][Recurring] Cancelling PayPal recurring profile '.$profileId.' for service sale '.$orderId);

        $update = ManageRecurringPaymentsProfileStatus(
            $profileId,
            BuyCoursesPlugin::PAYPAL_RECURRING_PAYMENT_CANCEL,
            'Cancelled by customer from BuyCourses service panel.'
        );

        $ack = strtoupper((string) ($update['ACK'] ?? ''));

        if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
            $errorCode = (string) ($update['L_ERRORCODE0'] ?? 'unknown');
            $longMessage = (string) ($update['L_LONGMESSAGE0'] ?? 'Unknown PayPal error.');

            error_log(
                '[BuyCourses][Recurring] ManageRecurringPaymentsProfileStatus failed for service sale '.$orderId.
                ' CODE='.$errorCode.
                ' MESSAGE='.$longMessage
            );

            $redirectWithError('PayPal error '.$errorCode.': '.$longMessage);
        }

        $plugin->updateServiceSaleRecurringData(
            $orderId,
            BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED,
            $profileId,
            null,
            api_get_utc_datetime()
        );

        $redirectWithSuccess('Recurring payment cancelled successfully.');
        break;

    default:
        api_not_allowed(true);
}
