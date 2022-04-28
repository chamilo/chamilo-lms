<?php
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Process payments for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$currentUserId = api_get_user_id();

$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(
        WEB_PLUGIN_PATH
    ).'buycourses/resources/css/style.css"/>';
$plugin = BuyCoursesPlugin::create();
$includeSession = $plugin->get('include_sessions') === 'true';
$paypalEnabled = $plugin->get('paypal_enable') === 'true';
$transferEnabled = $plugin->get('transfer_enable') === 'true';
$culqiEnabled = $plugin->get('culqi_enable') === 'true';
$tpvRedsysEnable = $plugin->get('tpv_redsys_enable') === 'true';
$stripeEnable = $plugin->get('stripe_enable') === 'true';
$tpvCecabankEnable = $plugin->get('cecabank_enable') === 'true';

if (!$paypalEnabled && !$transferEnabled && !$culqiEnabled && !$tpvRedsysEnable && !$stripeEnable && !$tpvCecabankEnable) {
    api_not_allowed(true);
}

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    api_not_allowed(true);
}

$currency = $plugin->getSelectedCurrency();
$buyingCourse = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$buyingSession = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_SESSION;
$queryString = 'i='.intval($_REQUEST['i']).'&t='.intval($_REQUEST['t']);

$coupon = null;

if (isset($_REQUEST['c'])) {
    $couponCode = $_REQUEST['c'];
    if ($buyingCourse) {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $_REQUEST['i']);
    } else {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $_REQUEST['i']);
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

    $saleId = $plugin->registerSale($item['id'], $formValues['payment_type'], $formValues['c']);

    if ($saleId !== false) {
        $_SESSION['bc_sale_id'] = $saleId;

        if (isset($formValues['c'])) {
            $couponSaleId = $plugin->registerCouponSale($saleId, $formValues['c']);
            if ($couponSaleId !== false) {
                $plugin->updateCouponDelivered($formValues['c']);
                $_SESSION['bc_coupon_id'] = $formValues['c'];
            }
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process_confirm.php');
    }

    exit;
}

$paymentTypesOptions = $plugin->getPaymentTypes(true);

$count = count($paymentTypesOptions);
if ($count === 0) {
    $form->addHtml($plugin->get_lang('NoPaymentOptionAvailable'));
    $form->addHtml('<br />');
    $form->addHtml('<br />');
} elseif ($count === 1) {
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

$form->addHidden('t', intval($_REQUEST['t']));
$form->addHidden('i', intval($_REQUEST['i']));
if ($coupon != null) {
    $form->addHidden('c', intval($coupon['id']));
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

    if ($coupon == null) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('CouponNotValid'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);
        exit;
    }

    Display::addFlash(
        Display::return_message($plugin->get_lang('CouponRedeemed'), 'success', false)
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process.php?i='.$_REQUEST['i'].'&t='.$_REQUEST['t'].'&c='.$formCouponValues['coupon_code']);

    exit;
}
$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', intval($_GET['t']));
$formCoupon->addHidden('i', intval($_GET['i']));
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

$content = $tpl->fetch('buycourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
