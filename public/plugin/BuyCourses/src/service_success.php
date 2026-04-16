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

$redirectToCatalog = static function (string $catalogUrl, array $params = []): void {
    $query = http_build_query($params);
    $redirectUrl = '' !== $query ? $catalogUrl.'?'.$query : $catalogUrl;

    header('Location: '.$redirectUrl);
    exit;
};

if ('' === $paypalUsername || '' === $paypalPassword || '' === $paypalSignature) {
    $redirectToCatalog($catalogUrl, [
        'payment_status' => 'error',
        'payment_reason' => 'paypal_credentials',
    ]);
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

        $redirectToCatalog($catalogUrl, [
            'payment_status' => 'cancelled',
        ]);
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

            unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id'], $_SESSION['TOKEN'], $_SESSION['payer_id']);

            $redirectToCatalog($catalogUrl, [
                'payment_status' => 'error',
                'payment_reason' => 'paypal_ack',
            ]);
        }

        switch ($paymentStatus) {
            case 'COMPLETED':
            case 'PROCESSED':
                $serviceSaleIsCompleted = $plugin->completeServiceSale((int) $serviceSale['id']);

                error_log(
                    'BuyCourses completeServiceSale result for sale '.$serviceSale['id'].': '.
                    var_export($serviceSaleIsCompleted, true)
                );

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id'], $_SESSION['TOKEN'], $_SESSION['payer_id']);

                if ($serviceSaleIsCompleted) {
                    $redirectToCatalog($catalogUrl, [
                        'payment_status' => 'completed',
                        'service_name' => (string) ($serviceSale['service']['name'] ?? ''),
                    ]);
                }

                $redirectToCatalog($catalogUrl, [
                    'payment_status' => 'error',
                    'payment_reason' => 'complete_service_sale',
                ]);

                break;

            case 'PENDING':
                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id'], $_SESSION['TOKEN'], $_SESSION['payer_id']);

                $redirectToCatalog($catalogUrl, [
                    'payment_status' => 'pending',
                    'payment_reason' => $pendingReason,
                ]);

                break;

            default:
                error_log(
                    'BuyCourses unexpected PayPal payment status for sale '.$serviceSale['id'].': '.$paymentStatus
                );

                unset($_SESSION['bc_service_sale_id'], $_SESSION['bc_coupon_id'], $_SESSION['TOKEN'], $_SESSION['payer_id']);

                $redirectToCatalog($catalogUrl, [
                    'payment_status' => 'error',
                    'payment_reason' => 'unexpected_status',
                ]);

                break;
        }
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
