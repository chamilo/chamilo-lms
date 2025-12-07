<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Process payments for the Buy Courses plugin.
 */
require_once '../config.php';

$currentUserId = api_get_user_id();

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
    WEB_PLUGIN_PATH
).'BuyCourses/resources/css/style.css"/>';
$plugin = BuyCoursesPlugin::create();
$includeSession = 'true' === $plugin->get('include_sessions');
$paypalEnabled = 'true' === $plugin->get('paypal_enable');
$transferEnabled = 'true' === $plugin->get('transfer_enable');
$culqiEnabled = 'true' === $plugin->get('culqi_enable');
$tpvRedsysEnable = 'true' === $plugin->get('tpv_redsys_enable');
$stripeEnable = 'true' === $plugin->get('stripe_enable');
$tpvCecabankEnable = 'true' === $plugin->get('cecabank_enable');

if (!$paypalEnabled && !$transferEnabled && !$culqiEnabled && !$tpvRedsysEnable && !$stripeEnable && !$tpvCecabankEnable) {
    api_not_allowed(true);
}

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    api_not_allowed(true);
}

$currency = $plugin->getSelectedCurrency();
$buyingCourse = BuyCoursesPlugin::PRODUCT_TYPE_COURSE === (int) $_REQUEST['t'];
$buyingSession = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === (int) $_REQUEST['t'];
$queryString = 'i='.(int) $_REQUEST['i'].'&t='.(int) $_REQUEST['t'];

$coupon = null;

if (isset($_REQUEST['c'])) {
    $couponId = (int) $_REQUEST['c'];
    if ($buyingCourse) {
        $coupon = $plugin->getCoupon($couponId, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $_REQUEST['i']);
    } else {
        $coupon = $plugin->getCoupon($couponId, BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $_REQUEST['i']);
    }
}

if (empty($currentUserId)) {
    Session::write('buy_course_redirect', api_get_self().'?'.$queryString);
    header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php');

    exit;
}

if ($buyingCourse) {
    $courseInfo = $plugin->getCourseInfo($_REQUEST['i'], $coupon);
    $item = $plugin->getItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
} elseif ($buyingSession) {
    $sessionInfo = $plugin->getSessionInfo($_REQUEST['i'], $coupon);
    $item = $plugin->getItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
}

$form = new FormValidator('confirm_sale');
if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    if (!$formValues['payment_type']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToSelectPaymentType'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);

        exit;
    }

    $saleId = $plugin->registerSale($item['id'], $formValues['payment_type'], (int) $formValues['c']);

    if ($saleId) {
        $_SESSION['bc_sale_id'] = $saleId;

        if (isset($formValues['c'])) {
            $couponSaleId = $plugin->registerCouponSale($saleId, $formValues['c']);
            if (false !== $couponSaleId) {
                $plugin->updateCouponDelivered($formValues['c']);
                $_SESSION['bc_coupon_id'] = $formValues['c'];
            }
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process_confirm.php');
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
    // get the only array item
    foreach ($paymentTypesOptions as $type => $value) {
        $form->addHtml(sprintf($plugin->get_lang('XIsOnlyPaymentMethodAvailable'), $value));
        $form->addHtml('<br />');
        $form->addHtml('<br />');
        $form->addHidden('payment_type', $type);
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

$form->addHidden('t', (int) $_REQUEST['t']);
$form->addHidden('i', (int) $_REQUEST['i']);
if (null != $coupon) {
    $form->addHidden('c', (int) $coupon['id']);
}
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success', 'btn-lg pull-right');

$formCoupon = new FormValidator('confirm_coupon');
if ($formCoupon->validate()) {
    $formCouponValues = $formCoupon->getSubmitValues();

    if (!$formCouponValues['coupon_code']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToAddCouponCode'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);

        exit;
    }

    if ($buyingCourse) {
        $coupon = $plugin->getCouponByCode($formCouponValues['coupon_code'], BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $_REQUEST['i']);
    } else {
        $coupon = $plugin->getCouponByCode($formCouponValues['coupon_code'], BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $_REQUEST['i']);
    }

    if (null == $coupon) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('CouponNotValid'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);

        exit;
    }

    Display::addFlash(
        Display::return_message($plugin->get_lang('CouponRedeemed'), 'success', false)
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process.php?i='.$_REQUEST['i'].'&t='.$_REQUEST['t'].'&c='.$coupon['id']);

    exit;
}
$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', (int) $_GET['t']);
$formCoupon->addHidden('i', (int) $_GET['i']);
$formCoupon->addButton('submit', $plugin->get_lang('RedeemCoupon'), 'check', 'success', 'btn-lg pull-right');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = ['url' => 'course_catalog.php', 'name' => $plugin->get_lang('CourseListOnSale')];

$tpl = new Template($templateName);
$tpl->assign('item_type', (int) $_GET['t']);
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
