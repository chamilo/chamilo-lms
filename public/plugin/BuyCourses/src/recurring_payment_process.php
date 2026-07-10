<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Enable PayPal recurring billing or cancel renewal for a completed renewable service sale.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$includeServices = 'true' === $plugin->get('include_services');
$paypalEnabled = 'true' === $plugin->get('paypal_enable');
$currentUserId = api_get_user_id();
$isJsonRequest = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
$panelUrl = api_get_path(WEB_PATH).'my-services';

$respondWithError = static function (string $message, int $statusCode = 400) use ($isJsonRequest, $panelUrl): never {
    if ($isJsonRequest) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    Display::addFlash(Display::return_message($message, 'error', false));
    header('Location: '.$panelUrl);
    exit;
};

$respondWithSuccess = static function (string $message) use ($isJsonRequest, $panelUrl): never {
    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    Display::addFlash(Display::return_message($message, 'success', false));
    header('Location: '.$panelUrl);
    exit;
};

if (!$includeServices || $currentUserId <= 0) {
    $respondWithError($plugin->get_lang('RecurringPaymentAccessDenied'), 403);
}

$orderId = isset($_REQUEST['order']) ? (int) $_REQUEST['order'] : (int) Session::read('bc_recurring_service_sale_id');
$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : (string) Session::read('bc_recurring_action');
$token = isset($_REQUEST['token']) ? trim((string) $_REQUEST['token']) : '';

if ('disable_recurring_payment' === $action) {
    $action = 'cancel_recurring_payment';
}

if ($orderId <= 0 || '' === $action) {
    $respondWithError($plugin->get_lang('RecurringPaymentInvalidRequest'));
}

$serviceSale = $plugin->getServiceSale($orderId);

if (empty($serviceSale) || (int) ($serviceSale['buyer']['id'] ?? 0) !== $currentUserId) {
    $respondWithError($plugin->get_lang('RecurringPaymentAccessDenied'), 403);
}

if ((int) ($serviceSale['status'] ?? BuyCoursesPlugin::SERVICE_STATUS_PENDING) !== BuyCoursesPlugin::SERVICE_STATUS_COMPLETED) {
    $respondWithError($plugin->get_lang('RecurringPaymentRequiresCompletedSale'));
}

if (empty($serviceSale['service']['renewable'])) {
    $respondWithError($plugin->get_lang('ServiceIsNotRenewable'));
}

$paypalParams = $plugin->getPaypalParams();
$isSandbox = 1 === (int) ($paypalParams['sandbox'] ?? 0);
$test = $isSandbox;
$pruebas = $isSandbox;
$paypalUsername = trim((string) ($paypalParams['username'] ?? ''));
$paypalPassword = trim((string) ($paypalParams['password'] ?? ''));
$paypalSignature = trim((string) ($paypalParams['signature'] ?? ''));

require_once 'paypalfunctions.php';

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
        if (!$paypalEnabled || (int) ($serviceSale['payment_type'] ?? 0) !== BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL) {
            $respondWithError($plugin->get_lang('RecurringPaymentRequiresPayPal'));
        }

        if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
            $respondWithError($plugin->get_lang('PayPalApiCredentialsIncomplete'));
        }

        if (BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED === (int) ($serviceSale['recurring_payment'] ?? 0)) {
            $respondWithSuccess($plugin->get_lang('RecurringPaymentAlreadyEnabled'));
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
                $longMessage = (string) ($expressCheckout['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));

                error_log(
                    '[BuyCourses][Recurring] SetExpressCheckout failed for service sale '.$orderId.
                    ' CODE='.$errorCode.
                    ' MESSAGE='.$longMessage
                );

                $respondWithError(sprintf($plugin->get_lang('PayPalErrorCodeMessage'), $errorCode, $longMessage));
            }

            RedirectToPayPal((string) ($expressCheckout['TOKEN'] ?? ''));
            exit;
        }

        $shippingDetails = GetShippingDetails($token);
        $shippingAck = strtoupper((string) ($shippingDetails['ACK'] ?? ''));

        if (!in_array($shippingAck, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
            $errorCode = (string) ($shippingDetails['L_ERRORCODE0'] ?? 'unknown');
            $longMessage = (string) ($shippingDetails['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));

            error_log(
                '[BuyCourses][Recurring] GetExpressCheckoutDetails failed for service sale '.$orderId.
                ' CODE='.$errorCode.
                ' MESSAGE='.$longMessage
            );

            $respondWithError(sprintf($plugin->get_lang('PayPalErrorCodeMessage'), $errorCode, $longMessage));
        }

        $durationDays = max(1, (int) ($serviceSale['service']['duration_days'] ?? 1));
        $totalCharges = max(0, (int) ($serviceSale['service']['total_charges'] ?? 0));
        $currency = $plugin->getCurrency((int) ($serviceSale['currency_id'] ?? 0));
        $currencyCode = strtoupper(trim((string) ($currency['iso_code'] ?? ($serviceSale['service']['currency'] ?? ''))));
        $amount = (float) ($serviceSale['recurring_amount'] ?? $serviceSale['price'] ?? 0);
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
            $longMessage = (string) ($recurringProfile['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));

            error_log(
                '[BuyCourses][Recurring] CreateRecurringPaymentsProfile failed for service sale '.$orderId.
                ' CODE='.$errorCode.
                ' MESSAGE='.$longMessage
            );

            $respondWithError(sprintf($plugin->get_lang('PayPalErrorCodeMessage'), $errorCode, $longMessage));
        }

        $previousProfileId = trim((string) ($serviceSale['recurring_profile_id'] ?? ''));
        $newProfileId = (string) ($recurringProfile['PROFILEID'] ?? '');

        if ('' !== $previousProfileId && $previousProfileId !== $newProfileId) {
            ManageRecurringPaymentsProfileStatus(
                $previousProfileId,
                BuyCoursesPlugin::PAYPAL_RECURRING_PAYMENT_CANCEL,
                $plugin->get_lang('RecurringPaymentReplacedByNewProfile')
            );
        }

        if ($plugin->updateServiceSaleGatewayData(
            $orderId,
            [
                'recurring_gateway' => 'paypal',
                'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED,
                'recurring_profile_id' => $newProfileId,
                'next_charge_date' => $billingStart->format('Y-m-d H:i:s'),
            ]
        )) {
            $plugin->recordAudit(
                BuyCoursesPlugin::AUDIT_ACTION_RENEWAL_ENABLED,
                BuyCoursesPlugin::AUDIT_OBJECT_SERVICE_SALE,
                $orderId,
                [
                    'gateway' => 'paypal',
                    'profile_id' => $newProfileId,
                    'next_charge_date' => $billingStart->format('Y-m-d H:i:s'),
                ],
                $currentUserId,
                BuyCoursesPlugin::AUDIT_SOURCE_USER
            );
        }

        Session::erase('bc_recurring_service_sale_id');
        Session::erase('bc_recurring_action');
        unset($_SESSION['TOKEN'], $_SESSION['payer_id']);

        $respondWithSuccess($plugin->get_lang('RecurringPaymentEnabledSuccessfully'));

    case 'cancel_recurring_payment':
        if ('POST' !== strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'))) {
            $respondWithError($plugin->get_lang('CancelRenewalPostRequired'), 405);
        }

        if (!Security::check_token('post')) {
            $respondWithError($plugin->get_lang('InvalidSecurityToken'), 403);
        }

        $recurringPayment = (int) ($serviceSale['recurring_payment'] ?? BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED);
        if (BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED === $recurringPayment
            || '' !== trim((string) ($serviceSale['cancelled_at'] ?? ''))
        ) {
            $respondWithSuccess($plugin->get_lang('RenewalAlreadyCancelled'));
        }

        if (BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED !== $recurringPayment) {
            $respondWithError($plugin->get_lang('RecurringPaymentIsNotEnabled'));
        }

        $gateway = strtolower(trim((string) ($serviceSale['recurring_gateway'] ?? '')));
        if ('' === $gateway) {
            $gateway = match ((int) ($serviceSale['payment_type'] ?? 0)) {
                BuyCoursesPlugin::PAYMENT_TYPE_STRIPE => 'stripe',
                BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL => 'paypal',
                default => '',
            };
        }

        $cancelledAt = api_get_utc_datetime();
        $plannedRenewalDate = trim((string) ($serviceSale['next_charge_date'] ?? ''));
        if ('' === $plannedRenewalDate) {
            $plannedRenewalDate = trim((string) ($serviceSale['date_end'] ?? ''));
        }

        if ('stripe' === $gateway) {
            $subscriptionId = trim((string) ($serviceSale['gateway_subscription_id'] ?? $serviceSale['recurring_profile_id'] ?? ''));
            $stripeParams = $plugin->getStripeParams();
            $secretKey = trim((string) ($stripeParams['secret_key'] ?? ''));

            if ('' === $subscriptionId || '' === $secretKey) {
                $respondWithError($plugin->get_lang('StripeCancellationConfigurationMissing'));
            }

            try {
                \Stripe\Stripe::setApiKey($secretKey);
                \Stripe\Stripe::setAppInfo('ChamiloBuyCoursesPlugin');

                $subscription = \Stripe\Subscription::retrieve($subscriptionId);
                $subscriptionStatus = strtolower((string) ($subscription->status ?? ''));

                if ('canceled' !== $subscriptionStatus && empty($subscription->cancel_at_period_end)) {
                    $subscription = \Stripe\Subscription::update($subscriptionId, [
                        'cancel_at_period_end' => true,
                    ]);
                }

                $periodEnd = (int) ($subscription->current_period_end ?? 0);
                if ($periodEnd <= 0) {
                    $periodEnd = (int) ($subscription->items->data[0]->current_period_end ?? 0);
                }

                if ($periodEnd > 0) {
                    $plannedRenewalDate = gmdate('Y-m-d H:i:s', $periodEnd);
                }

                $gatewayData = [
                    'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED,
                    'cancelled_at' => $cancelledAt,
                    'recurring_gateway' => 'stripe',
                    'gateway_subscription_id' => $subscriptionId,
                    'recurring_profile_id' => $subscriptionId,
                ];

                if ('' !== $plannedRenewalDate) {
                    $gatewayData['next_charge_date'] = $plannedRenewalDate;
                    $gatewayData['date_end'] = $plannedRenewalDate;
                }

                if (!$plugin->updateServiceSaleGatewayData($orderId, $gatewayData)) {
                    $respondWithError($plugin->get_lang('CancelRenewalGatewayFailed'));
                }

                $plugin->recordAudit(
                    BuyCoursesPlugin::AUDIT_ACTION_RENEWAL_CANCELLED,
                    BuyCoursesPlugin::AUDIT_OBJECT_SERVICE_SALE,
                    $orderId,
                    [
                        'gateway' => 'stripe',
                        'subscription_id' => $subscriptionId,
                        'planned_renewal_date' => $plannedRenewalDate,
                        'cancel_at_period_end' => true,
                    ],
                    $currentUserId,
                    BuyCoursesPlugin::AUDIT_SOURCE_USER
                );
            } catch (Throwable $exception) {
                error_log(
                    '[BuyCourses][Recurring] Stripe cancellation failed for service sale '.$orderId.
                    ' MESSAGE='.$exception->getMessage()
                );

                $respondWithError($plugin->get_lang('CancelRenewalGatewayFailed'));
            }

            $respondWithSuccess($plugin->get_lang('RenewalCancelledSuccessfully'));
        }

        if ('paypal' === $gateway) {
            if (!$paypalEnabled || '' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
                $respondWithError($plugin->get_lang('PayPalApiCredentialsIncomplete'));
            }

            $profileId = trim((string) ($serviceSale['recurring_profile_id'] ?? ''));
            if ('' === $profileId) {
                $respondWithError($plugin->get_lang('PayPalRecurringProfileMissing'));
            }

            error_log('[BuyCourses][Recurring] Cancelling PayPal recurring profile '.$profileId.' for service sale '.$orderId);

            $update = ManageRecurringPaymentsProfileStatus(
                $profileId,
                BuyCoursesPlugin::PAYPAL_RECURRING_PAYMENT_CANCEL,
                $plugin->get_lang('RecurringPaymentCancelledByCustomer')
            );

            $ack = strtoupper((string) ($update['ACK'] ?? ''));

            if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
                $errorCode = (string) ($update['L_ERRORCODE0'] ?? 'unknown');
                $longMessage = (string) ($update['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));

                error_log(
                    '[BuyCourses][Recurring] ManageRecurringPaymentsProfileStatus failed for service sale '.$orderId.
                    ' CODE='.$errorCode.
                    ' MESSAGE='.$longMessage
                );

                $respondWithError(sprintf($plugin->get_lang('PayPalErrorCodeMessage'), $errorCode, $longMessage));
            }

            $gatewayData = [
                'recurring_payment' => BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_CANCELLED,
                'cancelled_at' => $cancelledAt,
                'recurring_gateway' => 'paypal',
                'recurring_profile_id' => $profileId,
            ];

            if ('' !== $plannedRenewalDate) {
                $gatewayData['next_charge_date'] = $plannedRenewalDate;
            }

            if (!$plugin->updateServiceSaleGatewayData($orderId, $gatewayData)) {
                $respondWithError($plugin->get_lang('CancelRenewalGatewayFailed'));
            }

            $plugin->recordAudit(
                BuyCoursesPlugin::AUDIT_ACTION_RENEWAL_CANCELLED,
                BuyCoursesPlugin::AUDIT_OBJECT_SERVICE_SALE,
                $orderId,
                [
                    'gateway' => 'paypal',
                    'profile_id' => $profileId,
                    'planned_renewal_date' => $plannedRenewalDate,
                ],
                $currentUserId,
                BuyCoursesPlugin::AUDIT_SOURCE_USER
            );

            $respondWithSuccess($plugin->get_lang('RenewalCancelledSuccessfully'));
        }

        $respondWithError($plugin->get_lang('CancelRenewalUnsupportedGateway'));

    default:
        $respondWithError($plugin->get_lang('RecurringPaymentInvalidRequest'));
}
