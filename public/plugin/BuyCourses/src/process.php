<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Process payments for the Buy Courses plugin.
 */
require_once '../config.php';

$currentUserId = api_get_user_id();

$plugin = BuyCoursesPlugin::create();
$includeSession = 'true' === $plugin->get('include_sessions');
$paypalEnabled = 'true' === $plugin->get('paypal_enable');
$transferEnabled = 'true' === $plugin->get('transfer_enable');
$culqiEnabled = 'true' === $plugin->get('culqi_enable');
$tpvRedsysEnable = 'true' === $plugin->get('tpv_redsys_enable');
$stripeEnable = 'true' === $plugin->get('stripe_enable');
$tpvCecabankEnable = 'true' === $plugin->get('cecabank_enable');

$registrationUrl = api_get_path(WEB_CODE_PATH).'auth/registration.php';

$normalizeRelativePath = static function (string $url): string {
    $path = (string) parse_url($url, PHP_URL_PATH);
    $query = (string) parse_url($url, PHP_URL_QUERY);

    if ('' === $path) {
        $path = '/plugin/BuyCourses/src/process.php';
    }

    return '' !== $query ? $path.'?'.$query : $path;
};

if (
    !$paypalEnabled &&
    !$transferEnabled &&
    !$culqiEnabled &&
    !$tpvRedsysEnable &&
    !$stripeEnable &&
    !$tpvCecabankEnable
) {
    api_not_allowed(true);
}

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    api_not_allowed(true);
}

$productType = (int) $_REQUEST['t'];
$productId = (int) $_REQUEST['i'];

if ($productId <= 0 || $productType <= 0) {
    api_not_allowed(true);
}

$currency = $plugin->getSelectedCurrency();
$buyingCourse = BuyCoursesPlugin::PRODUCT_TYPE_COURSE === $productType;
$buyingSession = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === $productType;

if (!$buyingCourse && !$buyingSession) {
    api_not_allowed(true);
}

if ($buyingSession && !$includeSession) {
    api_not_allowed(true);
}

$queryString = 'i='.$productId.'&t='.$productType;

$coupon = null;
$couponId = null;

if (isset($_REQUEST['c']) && '' !== trim((string) $_REQUEST['c'])) {
    $couponId = (int) $_REQUEST['c'];

    if ($couponId > 0) {
        if ($buyingCourse) {
            $coupon = $plugin->getCoupon(
                $couponId,
                BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
                $productId
            );
        } else {
            $coupon = $plugin->getCoupon(
                $couponId,
                BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
                $productId
            );
        }
    }

    if ($couponId > 0) {
        $queryString .= '&c='.$couponId;
    }
}

if (empty($currentUserId)) {
    $currentPath = $normalizeRelativePath(
        (string) ($_SERVER['REQUEST_URI'] ?? (api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process.php?'.$queryString))
    );

    Session::write('buy_course_redirect', $currentPath);
    header('Location: '.$registrationUrl);
    exit;
}

$item = null;
$courseInfo = [];
$sessionInfo = [];

if ($buyingCourse) {
    $courseInfo = $plugin->getCourseInfo($productId, $coupon);
    $item = $plugin->getItemByProduct($productId, BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
} elseif ($buyingSession) {
    $sessionInfo = $plugin->getSessionInfo($productId, $coupon);
    $item = $plugin->getItemByProduct($productId, BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
}

if (empty($item)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('NoPaymentOptionAvailable'), 'error', false)
    );
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/index.php');
    exit;
}

$form = new FormValidator('confirm_sale');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $paymentType = isset($formValues['payment_type']) ? (int) $formValues['payment_type'] : 0;
    $submittedCouponId = isset($formValues['c']) ? (int) $formValues['c'] : null;

    if ($paymentType <= 0) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToSelectPaymentType'), 'error', false)
        );
        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    $saleId = $plugin->registerSale($item['id'], $paymentType, $submittedCouponId ?? 0);

    if ($saleId) {
        $_SESSION['bc_sale_id'] = $saleId;

        if (null !== $submittedCouponId && $submittedCouponId > 0) {
            $couponSaleId = $plugin->registerCouponSale($saleId, $submittedCouponId);

            if (false !== $couponSaleId) {
                $plugin->updateCouponDelivered($submittedCouponId);
                $_SESSION['bc_coupon_id'] = $submittedCouponId;
            }
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process_confirm.php');
        exit;
    }

    exit;
}

$paymentTypesOptions = $plugin->getPaymentTypes(true);

$count = count($paymentTypesOptions);
if (0 === $count) {
    $form->addHtml($plugin->get_lang('NoPaymentOptionAvailable'));
    $form->addHtml('<br />');
    $form->addHtml('<br />');
} elseif (1 === $count) {
    foreach ($paymentTypesOptions as $type => $value) {
        $form->addHtml(sprintf($plugin->get_lang('XIsOnlyPaymentMethodAvailable'), $value));
        $form->addHtml('<br />');
        $form->addHtml('<br />');
        $form->addHidden('payment_type', (int) $type);
    }
} else {
    $form->addHtml(
        Display::return_message(
            $plugin->get_lang('PleaseSelectThePaymentMethodBeforeConfirmYourOrder'),
            'info'
        )
    );
    $form->addRadio('payment_type', null, $paymentTypesOptions);
}

$form->addHidden('t', $productType);
$form->addHidden('i', $productId);

if (null !== $coupon && isset($coupon['id'])) {
    $form->addHidden('c', (int) $coupon['id']);
}

$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success', 'btn-lg pull-right');

$formCoupon = new FormValidator('confirm_coupon');

if ($formCoupon->validate()) {
    $formCouponValues = $formCoupon->getSubmitValues();
    $couponCode = trim((string) ($formCouponValues['coupon_code'] ?? ''));

    if ('' === $couponCode) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToAddCouponCode'), 'error', false)
        );
        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    if ($buyingCourse) {
        $coupon = $plugin->getCouponByCode(
            $couponCode,
            BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
            $productId
        );
    } else {
        $coupon = $plugin->getCouponByCode(
            $couponCode,
            BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
            $productId
        );
    }

    if (null == $coupon) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('CouponNotValid'), 'error', false)
        );
        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    Display::addFlash(
        Display::return_message($plugin->get_lang('CouponRedeemed'), 'success', false)
    );

    header(
        'Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process.php?i='.
        $productId.'&t='.$productType.'&c='.(int) $coupon['id']
    );
    exit;
}

$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', $productType);
$formCoupon->addHidden('i', $productId);
$formCoupon->addButton('submit', $plugin->get_lang('RedeemCoupon'), 'check', 'success', 'btn-lg pull-right');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = [
    'url' => $buyingCourse ? 'course_catalog.php' : 'session_catalog.php',
    'name' => $plugin->get_lang('CourseListOnSale'),
];

$tpl = new Template($templateName);
$tpl->assign('item_type', $productType);
$tpl->assign('buying_course', $buyingCourse);
$tpl->assign('buying_session', $buyingSession);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form_coupon', $formCoupon->returnForm());
$tpl->assign('form', $form->returnForm());

if ($buyingCourse) {
    $tpl->assign('course', $courseInfo);
} elseif ($buyingSession) {
    $tpl->assign('session', $sessionInfo);
}

$content = $tpl->fetch('BuyCourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
