<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use ChamiloSession as Session;
use Stripe\Stripe;

/**
 * Process purchase confirmation script for the Buy Courses plugin.
 */
$cidReset = true;

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$serviceSaleId = (int) Session::read('bc_service_sale_id');

if (empty($serviceSaleId)) {
    api_not_allowed(true);
}

$serviceSale = $plugin->getServiceSale($serviceSaleId);

if (empty($serviceSale)) {
    api_not_allowed(true);
}

$currentUserId = api_get_user_id();
$saleBuyerId = (int) ($serviceSale['buyer']['id'] ?? $serviceSale['buyer_id'] ?? 0);
if ($currentUserId <= 0 || $currentUserId !== $saleBuyerId) {
    api_not_allowed(true);
}

$userInfo = api_get_user_info($saleBuyerId);

$service = $serviceSale['service'];
$upgradeSourceSaleId = (int) ($serviceSale['upgrade_from_sale_id'] ?? 0);
$upgradeSourceSale = $upgradeSourceSaleId > 0
    ? $plugin->getServiceSale($upgradeSourceSaleId)
    : [];
$isExistingRecurringUpgrade = !empty($upgradeSourceSale)
    && BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED
        === (int) ($upgradeSourceSale['recurring_payment'] ?? BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_DISABLED)
    && '' !== trim((string) (
        $upgradeSourceSale['gateway_subscription_id']
        ?? $upgradeSourceSale['recurring_profile_id']
        ?? ''
    ));
$serviceItem = $plugin->buildServiceSaleVatSummary($serviceSale);
$currency = $plugin->getCurrency($serviceSale['currency_id']);
$globalParameters = $plugin->getGlobalParameters();
$terms = $globalParameters['terms_and_conditions'] ?? '';
$catalogUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';
$templateName = $plugin->get_lang('PaymentMethods');

$interbreadcrumb[] = [
    'url' => 'service_catalog.php',
    'name' => $plugin->get_lang('ListOfServicesOnSale'),
];


if (!function_exists('buycoursesGetStripeRecurringPriceDataInterval')) {
    /**
     * Map the service duration to a Stripe recurring interval.
     * Stripe Checkout requires a fixed recurring interval when using dynamic price_data.
     */
    function buycoursesGetStripeRecurringPriceDataInterval(array $service): array
    {
        $durationDays = max(0, (int) ($service['duration_days'] ?? 0));

        if ($durationDays >= 360) {
            return [
                'interval' => 'year',
                'interval_count' => max(1, (int) round($durationDays / 365)),
            ];
        }

        if ($durationDays >= 28) {
            return [
                'interval' => 'month',
                'interval_count' => max(1, (int) round($durationDays / 30)),
            ];
        }

        if ($durationDays >= 7) {
            return [
                'interval' => 'week',
                'interval_count' => max(1, (int) round($durationDays / 7)),
            ];
        }

        return [
            'interval' => 'day',
            'interval_count' => max(1, $durationDays ?: 1),
        ];
    }
}

if (!function_exists('buycoursesGetStripeObjectId')) {
    function buycoursesGetStripeObjectId(mixed $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_object($value) && isset($value->id)) {
            return trim((string) $value->id);
        }

        return '';
    }
}

if (!function_exists('buycoursesStripeCheckoutMatchesServiceSale')) {
    function buycoursesStripeCheckoutMatchesServiceSale(
        mixed $checkoutSession,
        array $serviceSale,
        int $currentUserId
    ): bool {
        $serviceSaleId = (int) ($serviceSale['id'] ?? 0);
        $saleBuyerId = (int) ($serviceSale['buyer']['id'] ?? $serviceSale['buyer_id'] ?? 0);
        $checkoutSessionId = trim((string) ($checkoutSession->id ?? ''));
        $storedCheckoutSessionId = trim((string) ($serviceSale['gateway_checkout_session_id'] ?? ''));
        $clientReferenceId = (int) ($checkoutSession->client_reference_id ?? 0);
        $metadataSaleId = (int) ($checkoutSession->metadata->service_sale_id ?? 0);
        $metadataBuyerId = (int) ($checkoutSession->metadata->buyer_id ?? 0);

        if ($serviceSaleId <= 0
            || $currentUserId <= 0
            || $currentUserId !== $saleBuyerId
            || '' === $checkoutSessionId
            || $serviceSaleId !== $clientReferenceId
            || $serviceSaleId !== $metadataSaleId
            || $currentUserId !== $metadataBuyerId
        ) {
            return false;
        }

        return '' === $storedCheckoutSessionId
            || hash_equals($storedCheckoutSessionId, $checkoutSessionId);
    }
}

if (!function_exists('buycoursesCompleteStripeServiceCheckout')) {
    function buycoursesCompleteStripeServiceCheckout(
        BuyCoursesPlugin $plugin,
        array $serviceSale,
        mixed $checkoutSession
    ): bool {
        $serviceSaleId = (int) ($serviceSale['id'] ?? 0);
        $checkoutSessionId = trim((string) ($checkoutSession->id ?? ''));
        if ($serviceSaleId <= 0 || '' === $checkoutSessionId) {
            return false;
        }

        $subscriptionId = buycoursesGetStripeObjectId($checkoutSession->subscription ?? null);
        $customerId = buycoursesGetStripeObjectId($checkoutSession->customer ?? null);
        $paymentIntentId = buycoursesGetStripeObjectId($checkoutSession->payment_intent ?? null);
        $gatewayData = [];

        if ('' !== $customerId) {
            $gatewayData['gateway_customer_id'] = $customerId;
        }

        if ('' !== $paymentIntentId) {
            $gatewayData['gateway_transaction_id'] = $paymentIntentId;
        }

        if ('' !== $subscriptionId) {
            $gatewayData['gateway_subscription_id'] = $subscriptionId;
            $gatewayData['recurring_profile_id'] = $subscriptionId;
            $gatewayData['recurring_gateway'] = 'stripe';
            $gatewayData['recurring_payment'] = BuyCoursesPlugin::SERVICE_RECURRING_PAYMENT_ENABLED;
        }

        if ([] !== $gatewayData) {
            $plugin->updateServiceSaleGatewayData($serviceSaleId, $gatewayData);
        }

        if (!$plugin->completeServiceSale($serviceSaleId)) {
            return false;
        }

        $completedServiceSale = $plugin->getServiceSale($serviceSaleId);
        $nextChargeDate = trim((string) ($completedServiceSale['date_end'] ?? ''));
        if ('' !== $subscriptionId && '' !== $nextChargeDate) {
            $plugin->updateServiceSaleGatewayData($serviceSaleId, [
                'next_charge_date' => $nextChargeDate,
            ]);
        }

        $plugin->markGatewayEventProcessed($serviceSaleId, $checkoutSessionId);

        return true;
    }
}

switch ($serviceSale['payment_type']) {
    case BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL:
        $paypalParams = $plugin->getPaypalParams();

        $isSandbox = !empty($paypalParams['sandbox']) && 1 == (int) $paypalParams['sandbox'];
        $paypalUsername = trim((string) ($paypalParams['username'] ?? ''));
        $paypalPassword = trim((string) ($paypalParams['password'] ?? ''));
        $paypalSignature = trim((string) ($paypalParams['signature'] ?? ''));

        if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('PayPalCredentialsIncomplete'),
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        $itemPrice = (float) $serviceSale['price'];
        $returnUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_success.php';
        $cancelUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_error.php';
        $extra = '';

        require_once 'paypalfunctions.php';

        $extra .= '&L_PAYMENTREQUEST_0_NAME0='.urlencode((string) $serviceSale['service']['name']);
        $extra .= '&L_PAYMENTREQUEST_0_QTY0=1';
        $extra .= '&L_PAYMENTREQUEST_0_AMT0='.urlencode(number_format($itemPrice, 2, '.', ''));

        $expressCheckout = CallShortcutExpressCheckout(
            $itemPrice,
            (string) $currency['iso_code'],
            'Sale',
            $returnUrl,
            $cancelUrl,
            $extra
        );

        $ack = strtoupper((string) ($expressCheckout['ACK'] ?? ''));

        if ('SUCCESS' !== $ack && 'SUCCESSWITHWARNING' !== $ack) {
            $errorCode = $expressCheckout['L_ERRORCODE0'] ?? 'Unknown';
            $errorMessage = $expressCheckout['L_LONGMESSAGE0'] ?? 'Unknown PayPal error';

            error_log(
                'BuyCourses SetExpressCheckout failed for service sale '.
                (int) $serviceSale['id'].
                ' | code='.$errorCode.
                ' | message='.$errorMessage
            );

            Display::addFlash(
                Display::return_message(
                    'PayPal checkout could not be initialized. Code: '.$errorCode.' | Message: '.$errorMessage,
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        if (empty($expressCheckout['TOKEN'])) {
            error_log('BuyCourses SetExpressCheckout succeeded without token for service sale '.(int) $serviceSale['id']);

            Display::addFlash(
                Display::return_message(
                    'PayPal checkout could not be initialized because no token was returned.',
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale($serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        /*
         * Do not send sale notification here.
         * The payment is not completed yet at this stage.
         */

        RedirectToPayPal((string) $expressCheckout['TOKEN']);
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER:
        $transferAccounts = $plugin->getTransferAccounts();

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message($plugin->get_lang('OrderCancelled'), 'error', false)
                );

                header('Location: '.$catalogUrl);
                exit;
            }

            if ('confirm' === $action) {
                $messageTemplate = new Template();
                $messageTemplate->assign(
                    'service_sale',
                    [
                        'name' => $serviceSale['service']['name'],
                        'buyer' => $serviceSale['buyer']['name'],
                        'buy_date' => $serviceSale['buy_date'],
                        'date_start' => $serviceSale['date_start'],
                        'date_end' => $serviceSale['date_end'],
                        'currency' => $currency['iso_code'],
                        'price' => $serviceSale['price'],
                        'reference' => $serviceSale['reference'],
                    ]
                );
                $messageTemplate->assign('transfer_accounts', $transferAccounts);

                $buyer = api_get_user_info($serviceSale['buyer']['id']);

                MessageManager::send_message_simple(
                    $buyer['user_id'],
                    $plugin->get_lang('bc_subject'),
                    $messageTemplate->fetch('BuyCourses/view/service_message_transfer.tpl')
                );

                if (!empty($globalParameters['sale_email'])) {
                    $messageConfirmTemplate = new Template();
                    $messageConfirmTemplate->assign('user', $userInfo);
                    $messageConfirmTemplate->assign(
                        'sale',
                        [
                            'date' => $serviceSale['buy_date'],
                            'product' => $serviceSale['service']['name'],
                            'currency' => $currency['iso_code'],
                            'price' => $serviceSale['price'],
                            'reference' => $serviceSale['reference'],
                        ]
                    );

                    api_mail_html(
                        '',
                        $globalParameters['sale_email'],
                        $plugin->get_lang('bc_subject'),
                        $messageConfirmTemplate->fetch('BuyCourses/view/message_confirm.tpl')
                    );
                }

                Display::addFlash(
                    Display::return_message(
                        sprintf(
                            $plugin->get_lang('PurchaseStatusX'),
                            $plugin->get_lang('PendingReasonByTransfer')
                        ),
                        'success',
                        false
                    )
                );

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                header('Location: '.$catalogUrl);
                exit;
            }
        }

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', $transferAccounts);
        $template->assign('is_bank_transfer', true);
        $template->assign('is_culqi_payment', false);
        $template->assign('is_cecabank_payment', false);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_CULQI:
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale($serviceSale['id']);
                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCancelled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.$catalogUrl);
                exit;
            }
        }

        $culqiParams = $plugin->getCulqiParams();
        $culqiPublicKey = trim((string) ($culqiParams['commerce_code'] ?? ''));

        if ('' === $culqiPublicKey) {
            Display::addFlash(
                Display::return_message(
                    'Culqi public key is not configured.',
                    'error',
                    false
                )
            );

            header('Location: '.$catalogUrl);
            exit;
        }

        $currencyCode = strtoupper(trim((string) ($currency['iso_code'] ?? 'PEN')));
        $amountInCents = (int) round((float) ($serviceSale['price'] ?? 0) * 100);

        if ($amountInCents <= 0) {
            Display::addFlash(
                Display::return_message(
                    'Invalid service sale amount.',
                    'error',
                    false
                )
            );

            header('Location: '.$catalogUrl);
            exit;
        }

        $htmlHeadXtra[] = '<script src="https://checkout.culqi.com/js/v4"></script>';

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', []);
        $template->assign('is_bank_transfer', false);
        $template->assign('is_culqi_payment', true);
        $template->assign('is_cecabank_payment', false);
        $template->assign('culqi_public_key', $culqiPublicKey);
        $template->assign(
            'culqi_charge_url',
            api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/buycourses.ajax.php?a=culqi_cargo_service'
        );
        $template->assign('catalog_url', $catalogUrl);
        $template->assign('culqi_amount_cents', $amountInCents);
        $template->assign('culqi_currency_code', $currencyCode);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_STRIPE:
        $stripeParams = $plugin->getStripeParams();
        $stripeSecretKey = trim((string) ($stripeParams['secret_key'] ?? ''));

        if ('' === $stripeSecretKey) {
            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('StripeSecretKeyMissing'),
                    'error',
                    false
                )
            );

            $plugin->cancelServiceSale((int) $serviceSale['id']);
            header('Location: '.$catalogUrl);
            exit;
        }

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale((int) $serviceSale['id']);
                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCancelled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.$catalogUrl);
                exit;
            }

            if ('confirm' === $action) {
                $currencyCode = strtolower(trim((string) ($currency['iso_code'] ?? 'eur')));
                $amountInCents = (int) round((float) ($serviceSale['price'] ?? 0) * 100);
                $recurringAmountInCents = (int) round(
                    (float) ($serviceSale['recurring_amount'] ?? $serviceSale['price'] ?? 0) * 100
                );
                $isRenewable = !empty($service['renewable']) && !$isExistingRecurringUpgrade;
                $serviceName = (string) ($service['name'] ?? $serviceSale['service']['name'] ?? 'Service');

                if ($amountInCents <= 0) {
                    Display::addFlash(
                        Display::return_message(
                            $plugin->get_lang('InvalidServiceSaleAmount'),
                            'error',
                            false
                        )
                    );

                    header('Location: '.$catalogUrl);
                    exit;
                }

                Stripe::setApiKey($stripeSecretKey);
                Stripe::setAppInfo('ChamiloBuyCoursesPlugin');

                $storedCheckoutSessionId = trim((string) ($serviceSale['gateway_checkout_session_id'] ?? ''));
                $checkoutAttemptSeed = 'initial';

                if ('' !== $storedCheckoutSessionId) {
                    try {
                        $storedCheckoutSession = \Stripe\Checkout\Session::retrieve($storedCheckoutSessionId);
                    } catch (Throwable $exception) {
                        error_log(
                            '[BuyCourses][Stripe] Stored Checkout Session could not be retrieved for service sale '.
                            (int) $serviceSale['id'].
                            ': '.
                            $exception->getMessage()
                        );

                        Display::addFlash(
                            Display::return_message(
                                $plugin->get_lang('StripeCheckoutCouldNotBeInitialized'),
                                'error',
                                false
                            )
                        );

                        header('Location: '.$catalogUrl);
                        exit;
                    }

                    if (!buycoursesStripeCheckoutMatchesServiceSale(
                        $storedCheckoutSession,
                        $serviceSale,
                        $currentUserId
                    )) {
                        error_log(
                            '[BuyCourses][Stripe] Stored Checkout Session does not match service sale '.
                            (int) $serviceSale['id'].
                            '.'
                        );

                        Display::addFlash(
                            Display::return_message(
                                $plugin->get_lang('StripeCheckoutCouldNotBeInitialized'),
                                'error',
                                false
                            )
                        );

                        header('Location: '.$catalogUrl);
                        exit;
                    }

                    $storedSessionStatus = strtolower(trim((string) ($storedCheckoutSession->status ?? '')));
                    $storedPaymentStatus = strtolower(trim((string) ($storedCheckoutSession->payment_status ?? '')));
                    $storedSessionIsPaid = in_array(
                        $storedPaymentStatus,
                        ['paid', 'no_payment_required'],
                        true
                    );

                    if ('complete' === $storedSessionStatus && $storedSessionIsPaid) {
                        if (buycoursesCompleteStripeServiceCheckout($plugin, $serviceSale, $storedCheckoutSession)) {
                            unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                            Display::addFlash(
                                Display::return_message(
                                    sprintf(
                                        $plugin->get_lang('SubscriptionToServiceXSuccessful'),
                                        htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8')
                                    ),
                                    'success',
                                    false
                                )
                            );

                            header('Location: '.$catalogUrl);
                            exit;
                        }

                        error_log(
                            '[BuyCourses][Stripe] Paid Checkout Session could not complete service sale '.
                            (int) $serviceSale['id'].
                            ': '.
                            $plugin->getLastServiceSaleError()
                        );

                        Display::addFlash(
                            Display::return_message(
                                $plugin->get_lang('UpgradeCouldNotBeCompleted'),
                                'error',
                                false
                            )
                        );

                        header('Location: '.$catalogUrl);
                        exit;
                    }

                    $storedCheckoutUrl = trim((string) ($storedCheckoutSession->url ?? ''));
                    if ('open' === $storedSessionStatus && '' !== $storedCheckoutUrl) {
                        header('HTTP/1.1 303 See Other');
                        header('Location: '.$storedCheckoutUrl);
                        exit;
                    }

                    if ('expired' !== $storedSessionStatus) {
                        Display::addFlash(
                            Display::return_message(
                                $plugin->get_lang('StripeCheckoutCompletedPendingConfirmation'),
                                'warning',
                                false
                            )
                        );

                        header('Location: '.$catalogUrl);
                        exit;
                    }

                    $checkoutAttemptSeed = $storedCheckoutSessionId;
                }

                $checkoutIdempotencyKey = 'buycourses-service-sale-'.
                    (int) $serviceSale['id'].
                    '-checkout-'.
                    substr(hash('sha256', $checkoutAttemptSeed), 0, 24);
                $successUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_stripe_success.php?service_sale_id='.(int) $serviceSale['id'].'&session_id={CHECKOUT_SESSION_ID}';
                $cancelUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_stripe_cancel.php?service_sale_id='.(int) $serviceSale['id'];

                $checkoutPayload = [
                    'payment_method_types' => ['card'],
                    'customer_email' => $userInfo['email'] ?? '',
                    'client_reference_id' => (string) $serviceSale['id'],
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'metadata' => [
                        'source' => 'BuyCourses',
                        'sale_type' => 'service',
                        'service_sale_id' => (string) $serviceSale['id'],
                        'service_id' => (string) $serviceSale['service_id'],
                        'buyer_id' => (string) ($serviceSale['buyer']['id'] ?? $serviceSale['buyer_id'] ?? 0),
                        'upgrade_from_sale_id' => (string) $upgradeSourceSaleId,
                    ],
                ];

                $priceData = [
                    'unit_amount' => $amountInCents,
                    'currency' => $currencyCode,
                    'product_data' => [
                        'name' => $serviceName,
                        'metadata' => [
                            'source' => 'BuyCourses',
                            'sale_type' => 'service',
                            'service_id' => (string) $serviceSale['service_id'],
                        ],
                    ],
                ];

                if ($isRenewable) {
                    if ($recurringAmountInCents <= 0) {
                        Display::addFlash(
                            Display::return_message(
                                $plugin->get_lang('InvalidServiceSaleAmount'),
                                'error',
                                false
                            )
                        );

                        header('Location: '.$catalogUrl);
                        exit;
                    }

                    $priceData['unit_amount'] = $recurringAmountInCents;
                    $recurringInterval = buycoursesGetStripeRecurringPriceDataInterval($service);
                    $priceData['recurring'] = [
                        'interval' => $recurringInterval['interval'],
                        'interval_count' => $recurringInterval['interval_count'],
                    ];

                    $checkoutPayload['mode'] = 'subscription';
                    $checkoutPayload['line_items'] = [[
                        'price_data' => $priceData,
                        'quantity' => 1,
                    ]];
                    $initialDiscountInCents = max(0, $recurringAmountInCents - $amountInCents);
                    if ($initialDiscountInCents > 0) {
                        try {
                            $stripeCoupon = \Stripe\Coupon::create(
                                [
                                    'amount_off' => $initialDiscountInCents,
                                    'currency' => $currencyCode,
                                    'duration' => 'once',
                                    'name' => $plugin->get_lang('UpgradeProratedCredit'),
                                    'metadata' => [
                                        'source' => 'BuyCourses',
                                        'service_sale_id' => (string) $serviceSale['id'],
                                        'upgrade_from_sale_id' => (string) $upgradeSourceSaleId,
                                    ],
                                ],
                                [
                                    'idempotency_key' => $checkoutIdempotencyKey.'-coupon',
                                ]
                            );
                            $checkoutPayload['discounts'] = [[
                                'coupon' => (string) $stripeCoupon->id,
                            ]];
                        } catch (Throwable $exception) {
                            error_log(
                                '[BuyCourses][Stripe] Upgrade coupon creation failed for service sale '.
                                (int) $serviceSale['id'].
                                ': '.
                                $exception->getMessage()
                            );

                            Display::addFlash(
                                Display::return_message(
                                    $plugin->get_lang('StripeCheckoutCouldNotBeInitialized'),
                                    'error',
                                    false
                                )
                            );

                            header('Location: '.$catalogUrl);
                            exit;
                        }
                    }

                    $checkoutPayload['subscription_data'] = [
                        'metadata' => [
                            'source' => 'BuyCourses',
                            'sale_type' => 'service',
                            'service_sale_id' => (string) $serviceSale['id'],
                            'service_id' => (string) $serviceSale['service_id'],
                            'buyer_id' => (string) ($serviceSale['buyer']['id'] ?? $serviceSale['buyer_id'] ?? 0),
                            'upgrade_from_sale_id' => (string) $upgradeSourceSaleId,
                            'vat_treatment' => (string) ($serviceSale['vat_treatment'] ?? ''),
                            'vat_rate' => (string) ($serviceSale['vat_rate'] ?? ''),
                            'tax_amount' => (string) ($serviceSale['tax_amount'] ?? ''),
                            'price_without_tax' => (string) ($serviceSale['price_without_tax'] ?? ''),
                        ],
                    ];
                } else {
                    $checkoutPayload['mode'] = 'payment';
                    $checkoutPayload['line_items'] = [[
                        'price_data' => $priceData,
                        'quantity' => 1,
                    ]];
                }

                try {
                    $checkoutSession = \Stripe\Checkout\Session::create(
                        $checkoutPayload,
                        [
                            'idempotency_key' => $checkoutIdempotencyKey,
                        ]
                    );
                } catch (Throwable $exception) {
                    error_log('[BuyCourses][Stripe] Checkout Session creation failed for service sale '.(int) $serviceSale['id'].': '.$exception->getMessage());

                    Display::addFlash(
                        Display::return_message(
                            $plugin->get_lang('StripeCheckoutCouldNotBeInitialized'),
                            'error',
                            false
                        )
                    );

                    header('Location: '.$catalogUrl);
                    exit;
                }

                if (empty($checkoutSession->id) || empty($checkoutSession->url)) {
                    Display::addFlash(
                        Display::return_message(
                            $plugin->get_lang('StripeCheckoutCouldNotBeInitialized'),
                            'error',
                            false
                        )
                    );

                    header('Location: '.$catalogUrl);
                    exit;
                }

                $gatewayData = [
                    'gateway_checkout_session_id' => (string) $checkoutSession->id,
                ];
                if ($isRenewable) {
                    $gatewayData['recurring_gateway'] = 'stripe';
                }

                if (!$plugin->updateServiceSaleGatewayData((int) $serviceSale['id'], $gatewayData)) {
                    error_log(
                        '[BuyCourses][Stripe] Checkout Session could not be stored for service sale '.
                        (int) $serviceSale['id'].
                        '.'
                    );

                    Display::addFlash(
                        Display::return_message(
                            $plugin->get_lang('StripeCheckoutCouldNotBeInitialized'),
                            'error',
                            false
                        )
                    );

                    header('Location: '.$catalogUrl);
                    exit;
                }

                unset($_SESSION['bc_coupon_id']);

                header('HTTP/1.1 303 See Other');
                header('Location: '.$checkoutSession->url);
                exit;
            }
        }

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', []);
        $template->assign('is_bank_transfer', false);
        $template->assign('is_culqi_payment', false);
        $template->assign('is_cecabank_payment', false);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    case BuyCoursesPlugin::PAYMENT_TYPE_TPV_CECABANK:
        $cecabankParams = $plugin->getcecabankParams();

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $action = trim((string) ($_POST['action'] ?? ''));

            if ('cancel' === $action) {
                $plugin->cancelServiceSale($serviceSale['id']);

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id']);

                Display::addFlash(
                    Display::return_message(
                        $plugin->get_lang('OrderCancelled'),
                        'warning',
                        false
                    )
                );

                header('Location: '.$catalogUrl);
                exit;
            }

            if ('confirm' === $action) {
                $urlTpv = $cecabankParams['merchart_id'];
                $signature = $plugin->getCecabankSignature(
                    $serviceSale['reference'],
                    $serviceSale['price']
                );

                echo '<form name="tpv_chamilo" action="'.$urlTpv.'" method="POST">';
                echo '<input type="hidden" name="MerchantID" value="'.$cecabankParams['merchant_id'].'" />';
                echo '<input type="hidden" name="AcquirerBIN" value="'.$cecabankParams['acquirer_bin'].'" />';
                echo '<input type="hidden" name="TerminalID" value="'.$cecabankParams['terminal_id'].'" />';
                echo '<input type="hidden" name="URL_OK" value="'.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/cecabank_success.php" />';
                echo '<input type="hidden" name="URL_NOK" value="'.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/cecabank_cancel.php" />';
                echo '<input type="hidden" name="Firma" value="'.$signature.'" />';
                echo '<input type="hidden" name="Cifrado" value="'.$cecabankParams['cypher'].'" />';
                echo '<input type="hidden" name="Num_operacion" value="'.$serviceSale['reference'].'" />';
                echo '<input type="hidden" name="Importe" value="'.($serviceSale['price'] * 100).'" />';
                echo '<input type="hidden" name="TipoMoneda" value="'.$cecabankParams['currency'].'" />';
                echo '<input type="hidden" name="Exponente" value="'.$cecabankParams['exponent'].'" />';
                echo '<input type="hidden" name="Pago_soportado" value="'.$cecabankParams['supported_payment'].'" />';
                echo '</form>';
                echo '<script>document.tpv_chamilo.submit();</script>';

                exit;
            }
        }

        $template = new Template($templateName);
        $template->assign('header', $templateName);
        $template->assign('back_url', $catalogUrl);
        $template->assign('confirm_url', api_get_self());
        $template->assign('terms', $terms);
        $template->assign('service_sale', $serviceSale);
        $template->assign('service', $service);
        $template->assign('service_item', $serviceItem);
        $template->assign('currency', $currency);
        $template->assign('user', $userInfo);
        $template->assign('transfer_accounts', []);
        $template->assign('is_bank_transfer', false);
        $template->assign('is_culqi_payment', false);
        $template->assign('is_cecabank_payment', true);

        $content = $template->fetch('BuyCourses/view/process_confirm.tpl');
        $template->assign('content', $content);
        $template->display_one_col_template();
        exit;

    default:
        api_not_allowed(true);
}
