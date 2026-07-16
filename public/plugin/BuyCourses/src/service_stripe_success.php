<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$catalogUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';
$serviceSaleId = isset($_GET['service_sale_id']) ? (int) $_GET['service_sale_id'] : 0;
$checkoutSessionId = isset($_GET['session_id']) ? trim((string) $_GET['session_id']) : '';
$currentUserId = api_get_user_id();
$completed = false;
$serviceName = '';

$getStripeObjectId = static function (mixed $value): string {
    if (is_string($value)) {
        return trim($value);
    }

    if (is_object($value) && isset($value->id)) {
        return trim((string) $value->id);
    }

    return '';
};

if ($serviceSaleId > 0 && '' !== $checkoutSessionId && $currentUserId > 0) {
    $serviceSale = $plugin->getServiceSale($serviceSaleId);
    $saleBuyerId = (int) ($serviceSale['buyer']['id'] ?? $serviceSale['buyer_id'] ?? 0);
    $storedCheckoutSessionId = trim((string) ($serviceSale['gateway_checkout_session_id'] ?? ''));
    $serviceName = (string) ($serviceSale['service']['name'] ?? '');
    $isUpgrade = (int) ($serviceSale['upgrade_from_sale_id'] ?? 0) > 0;
    $isAlreadyCompleted = BuyCoursesPlugin::SERVICE_STATUS_COMPLETED
        === (int) ($serviceSale['status'] ?? BuyCoursesPlugin::SERVICE_STATUS_PENDING)
        && (!$isUpgrade || !empty($serviceSale['upgrade_completed_at']));

    if ($saleBuyerId === $currentUserId && hash_equals($storedCheckoutSessionId, $checkoutSessionId)) {
        if ($isAlreadyCompleted) {
            $completed = true;
        } else {
            $stripeParams = $plugin->getStripeParams();
            $secretKey = trim((string) ($stripeParams['secret_key'] ?? ''));

            if ('' !== $secretKey) {
                try {
                    \Stripe\Stripe::setApiKey($secretKey);
                    \Stripe\Stripe::setAppInfo('ChamiloBuyCoursesPlugin');

                    $checkoutSession = \Stripe\Checkout\Session::retrieve($checkoutSessionId);
                    $sessionStatus = strtolower(trim((string) ($checkoutSession->status ?? '')));
                    $paymentStatus = strtolower(trim((string) ($checkoutSession->payment_status ?? '')));
                    $clientReferenceId = (int) ($checkoutSession->client_reference_id ?? 0);
                    $metadataSaleId = (int) ($checkoutSession->metadata->service_sale_id ?? 0);
                    $metadataBuyerId = (int) ($checkoutSession->metadata->buyer_id ?? 0);
                    $isPaid = in_array($paymentStatus, ['paid', 'no_payment_required'], true);
                    $matchesSale = $serviceSaleId === $clientReferenceId
                        && $serviceSaleId === $metadataSaleId
                        && $currentUserId === $metadataBuyerId;

                    if ('complete' === $sessionStatus && $isPaid && $matchesSale) {
                        $subscriptionId = $getStripeObjectId($checkoutSession->subscription ?? null);
                        $customerId = $getStripeObjectId($checkoutSession->customer ?? null);
                        $paymentIntentId = $getStripeObjectId($checkoutSession->payment_intent ?? null);
                        $gatewayData = [
                            'gateway_customer_id' => '' !== $customerId ? $customerId : null,
                            'gateway_transaction_id' => '' !== $paymentIntentId ? $paymentIntentId : null,
                        ];

                        if ('' !== $subscriptionId) {
                            $gatewayData['gateway_subscription_id'] = $subscriptionId;
                            $gatewayData['recurring_profile_id'] = $subscriptionId;
                            $gatewayData['recurring_gateway'] = 'stripe';
                            $gatewayData['recurring_payment'] = BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED;
                        }

                        $plugin->updateServiceSaleGatewayData($serviceSaleId, $gatewayData);
                        if ($plugin->completeServiceSale(
                            $serviceSaleId,
                            BuyCoursesPlugin::AUDIT_SOURCE_GATEWAY,
                            $currentUserId,
                            [
                                'gateway' => 'stripe',
                                'checkout_session_id' => $checkoutSessionId,
                                'subscription_id' => $subscriptionId,
                                'transaction_id' => $paymentIntentId,
                                'trigger' => 'checkout_return',
                            ]
                        )) {
                            $completedServiceSale = $plugin->getServiceSale($serviceSaleId);
                            $nextChargeDate = trim((string) ($completedServiceSale['date_end'] ?? ''));

                            if ('' !== $subscriptionId && '' !== $nextChargeDate) {
                                $plugin->updateServiceSaleGatewayData($serviceSaleId, [
                                    'next_charge_date' => $nextChargeDate,
                                ]);
                            }

                            $plugin->markGatewayEventProcessed($serviceSaleId, $checkoutSessionId);
                            $completed = true;
                        } else {
                            error_log(
                                '[BuyCourses][Stripe] Checkout return could not complete service sale. sale_id='.
                                $serviceSaleId.
                                ' error='.
                                $plugin->getLastServiceSaleError()
                            );
                        }
                    } else {
                        error_log(
                            '[BuyCourses][Stripe] Checkout return validation failed. sale_id='.
                            $serviceSaleId.
                            ' session_status='.
                            $sessionStatus.
                            ' payment_status='.
                            $paymentStatus
                        );
                    }
                } catch (Throwable $exception) {
                    error_log(
                        '[BuyCourses][Stripe] Checkout return reconciliation failed. sale_id='.
                        $serviceSaleId.
                        ' error='.
                        $exception->getMessage()
                    );
                }
            }
        }
    }
}

if ($completed) {
    unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

    $message = sprintf(
        $plugin->get_lang('SubscriptionToServiceXSuccessful'),
        htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8')
    );
    $messageType = 'success';
} else {
    $message = $plugin->get_lang('StripeCheckoutCompletedPendingConfirmation');
    $messageType = 'warning';
}

Display::addFlash(
    Display::return_message(
        $message,
        $messageType,
        false
    )
);

header('Location: '.$catalogUrl);
exit;
