<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Success page for the purchase of a service in the Buy Courses plugin.
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$paypalEnabled = 'true' === $plugin->get('paypal_enable');

if (!$paypalEnabled) {
    api_not_allowed(true);
}

$serviceSaleId = isset($_SESSION['bc_service_sale_id']) ? (int) $_SESSION['bc_service_sale_id'] : 0;

if ($serviceSaleId <= 0) {
    api_not_allowed(true);
}

$serviceSale = $plugin->getServiceSale($serviceSaleId);

if (empty($serviceSale)) {
    api_not_allowed(true);
}

$couponId = isset($_SESSION['bc_coupon_id']) ? (int) $_SESSION['bc_coupon_id'] : 0;
$serviceId = (int) ($serviceSale['service_id'] ?? 0);

$serviceItem = [];
if ($couponId > 0) {
    $coupon = $plugin->getCouponService($couponId, $serviceId);

    if (!empty($coupon)) {
        $serviceItem = $plugin->getService($serviceId, $coupon);
    }
}

if (empty($serviceItem)) {
    $serviceItem = $plugin->getService($serviceId);
}

$service = $serviceSale['service'];
$userInfo = api_get_user_info((int) $serviceSale['buyer']['id']);
$itemPrice = (float) $serviceSale['price'];

$currency = $plugin->getCurrency((int) $serviceSale['currency_id']);
$currencyIso = $currency['iso_code'] ?? '';

$globalParameters = $plugin->getGlobalParameters();
$catalogUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/service_catalog.php';

$paypalParams = $plugin->getPaypalParams();

$pruebas = !empty($paypalParams['sandbox']) && 1 == (int) $paypalParams['sandbox'];
$paypalUsername = (string) ($paypalParams['username'] ?? '');
$paypalPassword = (string) ($paypalParams['password'] ?? '');
$paypalSignature = (string) ($paypalParams['signature'] ?? '');

if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
    Display::addFlash(
        Display::return_message('PayPal credentials are incomplete.', 'error', false)
    );

    header('Location: '.$catalogUrl);
    exit;
}

require_once 'paypalfunctions.php';

$token = isset($_GET['token']) ? Security::remove_XSS($_GET['token']) : ($_SESSION['TOKEN'] ?? null);
$payerId = isset($_GET['PayerID']) ? Security::remove_XSS($_GET['PayerID']) : ($_SESSION['payer_id'] ?? null);

if (empty($token)) {
    api_not_allowed(true);
}

$_SESSION['TOKEN'] = $token;

if (!empty($payerId)) {
    $_SESSION['payer_id'] = $payerId;
}

$buyerInformation = GetShippingDetails(urlencode($token));

if (!empty($buyerInformation['PAYERID'])) {
    $_SESSION['payer_id'] = $buyerInformation['PAYERID'];
}

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    $action = trim((string) ($_POST['action'] ?? ''));

    if ('cancel' === $action) {
        $plugin->cancelServiceSale((int) $serviceSale['id']);

        unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id'], $_SESSION['TOKEN'], $_SESSION['payer_id']);

        Display::addFlash(
            Display::return_message($plugin->get_lang('OrderCancelled'), 'error', false)
        );

        header('Location: '.$catalogUrl);
        exit;
    }

    if ('confirm' === $action) {
        $confirmPayments = ConfirmPayment($itemPrice);

        $ack = strtoupper((string) ($confirmPayments['ACK'] ?? ''));
        $paymentStatus = strtoupper((string) ($confirmPayments['PAYMENTINFO_0_PAYMENTSTATUS'] ?? ''));
        $pendingReason = (string) ($confirmPayments['PAYMENTINFO_0_PENDINGREASON'] ?? '');

        error_log('BuyCourses PayPal ConfirmPayment ACK for sale '.$serviceSale['id'].': '.$ack);
        error_log('BuyCourses PayPal payment status for sale '.$serviceSale['id'].': '.$paymentStatus);
        error_log('BuyCourses PayPal pending reason for sale '.$serviceSale['id'].': '.$pendingReason);

        if ('SUCCESS' !== $ack && 'SUCCESSWITHWARNING' !== $ack) {
            $errorCode = $confirmPayments['L_ERRORCODE0'] ?? 'Unknown';
            $errorMessage = $confirmPayments['L_LONGMESSAGE0'] ?? 'Unknown PayPal error';

            error_log(
                'BuyCourses PayPal ConfirmPayment failed for sale '.$serviceSale['id'].
                ' | code='.$errorCode.
                ' | message='.$errorMessage
            );

            Display::addFlash(
                Display::return_message(
                    'PayPal ACK: '.$ack.' | Code: '.$errorCode.' | Message: '.$errorMessage,
                    'error',
                    false
                )
            );

            header('Location: '.$catalogUrl);
            exit;
        }

        switch ($paymentStatus) {
            case 'COMPLETED':
            case 'PROCESSED':
                $serviceSaleIsCompleted = $plugin->completeServiceSale((int) $serviceSale['id']);
                error_log(
                    'BuyCourses completeServiceSale result for sale '.$serviceSale['id'].': '.
                    var_export($serviceSaleIsCompleted, true)
                );

                if ($serviceSaleIsCompleted) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(
                                $plugin->get_lang('SubscriptionToServiceXSuccessful'),
                                $serviceSale['service']['name']
                            ),
                            'success',
                            false
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            $plugin->get_lang('ErrorContactPlatformAdmin'),
                            'error',
                            false
                        )
                    );
                }

                break;

            case 'PENDING':
                switch ($pendingReason) {
                    case 'address':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByAddress');
                        break;
                    case 'authorization':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByAuthorization');
                        break;
                    case 'echeck':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByEcheck');
                        break;
                    case 'intl':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByIntl');
                        break;
                    case 'multicurrency':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByMulticurrency');
                        break;
                    case 'order':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByOrder');
                        break;
                    case 'paymentreview':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByPaymentReview');
                        break;
                    case 'regulatoryreview':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByRegulatoryReview');
                        break;
                    case 'unilateral':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByUnilateral');
                        break;
                    case 'upgrade':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByUpgrade');
                        break;
                    case 'verify':
                        $purchaseStatus = $plugin->get_lang('PendingReasonByVerify');
                        break;
                    case 'other':
                    default:
                        $purchaseStatus = $plugin->get_lang('PendingReasonByOther');
                        break;
                }

                Display::addFlash(
                    Display::return_message(
                        'PayPal payment is pending: '.$purchaseStatus,
                        'warning',
                        false
                    )
                );

                break;

            default:
                error_log(
                    'BuyCourses unexpected PayPal payment status for sale '.$serviceSale['id'].': '.$paymentStatus
                );

                Display::addFlash(
                    Display::return_message(
                        'Unexpected PayPal payment status: '.$paymentStatus,
                        'error',
                        false
                    )
                );

                break;
        }

        unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id'], $_SESSION['TOKEN'], $_SESSION['payer_id']);

        header('Location: '.$catalogUrl);
        exit;
    }
}

$interbreadcrumb[] = [
    'url' => 'service_catalog.php',
    'name' => $plugin->get_lang('ListOfServicesOnSale'),
];

$templateName = $plugin->get_lang('PaymentMethods');
$tpl = new Template($templateName);
$tpl->assign('header', $templateName);
$tpl->assign('title', $service['name'] ?? $plugin->get_lang('PaymentMethods'));
$tpl->assign('service_sale', $serviceSale);
$tpl->assign('service', $service);
$tpl->assign('service_item', $serviceItem);
$tpl->assign('user', $userInfo);
$tpl->assign('currency_iso', $currencyIso);
$tpl->assign('price', $serviceSale['price']);
$tpl->assign('back_url', $catalogUrl);

$content = $tpl->fetch('BuyCourses/view/success.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
