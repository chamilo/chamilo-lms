<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$paypalEnabled = 'true' === $plugin->get('paypal_enable');

if (!$paypalEnabled) {
    api_not_allowed(true);
}

$saleId = (int) ($_SESSION['bc_sale_id'] ?? 0);
$couponId = (int) ($_SESSION['bc_coupon_id'] ?? 0);

if (empty($saleId)) {
    api_not_allowed(true);
}

$sale = $plugin->getSubscriptionSale($saleId);

if (empty($sale)) {
    api_not_allowed(true);
}

$coupon = [];
if ($couponId > 0) {
    $coupon = $plugin->getCoupon(
        $couponId,
        (int) $sale['product_type'],
        (int) $sale['product_id']
    );
}

$buyingCourse = false;
$buyingSession = false;
$course = [];
$session = [];

switch ((int) $sale['product_type']) {
    case BuyCoursesPlugin::PRODUCT_TYPE_COURSE:
        $buyingCourse = true;
        $course = $plugin->getSubscriptionCourseInfo((int) $sale['product_id'], $coupon);
        break;

    case BuyCoursesPlugin::PRODUCT_TYPE_SESSION:
        $buyingSession = true;
        $session = $plugin->getSubscriptionSessionInfo((int) $sale['product_id'], $coupon);
        break;

    default:
        api_not_allowed(true);
}

$currency = $plugin->getCurrency((int) $sale['currency_id']);
$currencyCode = $currency['iso_code'] ?? '';
$formattedSalePrice = trim($currencyCode.' '.api_number_format((float) $sale['price'], 2));

if ($buyingCourse && !empty($course)) {
    $course['name'] = $course['title'] ?? '';
    $course['currency'] = $currencyCode;
    $course['price'] = $sale['price'];
    $course['total_price_formatted'] = $formattedSalePrice;
}

if ($buyingSession && !empty($session)) {
    if (!isset($session['title']) && isset($session['name'])) {
        $session['title'] = $session['name'];
    }

    $session['currency'] = $currencyCode;
    $session['price'] = $sale['price'];
    $session['total_price_formatted'] = $formattedSalePrice;
}

$paypalParams = $plugin->getPaypalParams();
$test = 1 == ($paypalParams['sandbox'] ?? 0);
$paypalUsername = $paypalParams['username'] ?? '';
$paypalPassword = $paypalParams['password'] ?? '';
$paypalSignature = $paypalParams['signature'] ?? '';

require_once 'paypalfunctions.php';

$redirectUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/'.(
    $buyingSession ? 'subscription_session_catalog.php' : 'subscription_course_catalog.php'
    );

$form = new FormValidator(
    'success',
    'POST',
    api_get_self(),
    null,
    null,
    FormValidator::LAYOUT_INLINE
);
$form->addButton('confirm', $plugin->get_lang('ConfirmOrder'), 'check', 'success');
$form->addButtonCancel($plugin->get_lang('CancelOrder'), 'cancel');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    if (isset($formValues['cancel'])) {
        $plugin->cancelSubscriptionSale((int) $sale['id']);

        unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);

        Display::addFlash(
            Display::return_message($plugin->get_lang('OrderCancelled'), 'warning', false)
        );

        header('Location: '.$redirectUrl);
        exit;
    }

    $confirmPayments = ConfirmPayment((string) $sale['price']);
    $ack = strtoupper((string) ($confirmPayments['ACK'] ?? ''));

    if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
        $errorCode = (string) ($confirmPayments['L_ERRORCODE0'] ?? 'unknown');
        $longMessage = (string) ($confirmPayments['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));

        $errorMessage = vsprintf(
            $plugin->get_lang('ErrorOccurred'),
            [$errorCode, $longMessage]
        );

        Display::addFlash(
            Display::return_message($errorMessage, 'error', false)
        );

        header('Location: '.$redirectUrl);
        exit;
    }

    $paymentStatus = (string) ($confirmPayments['PAYMENTINFO_0_PAYMENTSTATUS'] ?? '');

    switch ($paymentStatus) {
        case 'Completed':
            $saleIsCompleted = $plugin->completeSubscriptionSale((int) $sale['id']);

            if ($saleIsCompleted) {
                Display::addFlash(
                    $plugin->getSubscriptionSuccessMessage($sale)
                );
                $plugin->storeSubscriptionPayouts((int) $sale['id']);
            } else {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
                );
            }
            break;

        case 'Pending':
            $pendingReason = (string) ($confirmPayments['PAYMENTINFO_0_PENDINGREASON'] ?? '');

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
                    sprintf($plugin->get_lang('PurchaseStatusX'), $purchaseStatus),
                    'warning',
                    false
                )
            );
            break;

        default:
            $plugin->cancelSubscriptionSale((int) $sale['id']);

            Display::addFlash(
                Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error')
            );
            break;
    }

    unset($_SESSION['bc_sale_id'], $_SESSION['bc_coupon_id']);

    header('Location: '.$redirectUrl);
    exit;
}

$token = isset($_GET['token']) ? Security::remove_XSS($_GET['token']) : null;

if (empty($token)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('ErrorContactPlatformAdmin'), 'error', false)
    );

    header('Location: '.$redirectUrl);
    exit;
}

$shippingDetails = GetShippingDetails($token);
$ack = strtoupper((string) ($shippingDetails['ACK'] ?? ''));

if (!in_array($ack, ['SUCCESS', 'SUCCESSWITHWARNING'], true)) {
    $errorCode = (string) ($shippingDetails['L_ERRORCODE0'] ?? 'unknown');
    $longMessage = (string) ($shippingDetails['L_LONGMESSAGE0'] ?? $plugin->get_lang('UnknownPayPalError'));

    $errorMessage = vsprintf(
        $plugin->get_lang('ErrorOccurred'),
        [$errorCode, $longMessage]
    );

    Display::addFlash(
        Display::return_message($errorMessage, 'error', false)
    );

    header('Location: '.$redirectUrl);
    exit;
}

$interbreadcrumb[] = [
    'url' => $buyingSession ? 'subscription_session_catalog.php' : 'subscription_course_catalog.php',
    'name' => $buyingSession ? $plugin->get_lang('SessionListOnSale') : $plugin->get_lang('CourseListOnSale'),
];

$templateName = $plugin->get_lang('PaymentMethods');
$tpl = new Template($templateName);

if ($buyingCourse) {
    $tpl->assign('course', $course);
} elseif ($buyingSession) {
    $tpl->assign('session', $session);
}

$tpl->assign('buying_course', $buyingCourse);
$tpl->assign('buying_session', $buyingSession);
$tpl->assign('title', $sale['product_name']);
$tpl->assign('price', $sale['price']);
$tpl->assign('currency', $currencyCode);
$tpl->assign('user', api_get_user_info((int) $sale['user_id']));
$tpl->assign('form', $form->returnForm());

$content = $tpl->fetch('BuyCourses/view/success.tpl');
$tpl->assign('content', $content);
$tpl->display_one_col_template();
