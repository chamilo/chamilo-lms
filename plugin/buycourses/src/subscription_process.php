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

if (!$paypalEnabled && !$transferEnabled && !$culqiEnabled && !$tpvRedsysEnable) {
    api_not_allowed(true);
}

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    api_not_allowed(true);
}

$buyingCourse = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$buyingSession = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_SESSION;
$queryString = 'i='.intval($_REQUEST['i']).'&t='.intval($_REQUEST['t']);

if (isset($_REQUEST['c'])) {
    $couponCode = $_REQUEST['c'];
    if ($buyingCourse) {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $_REQUEST['i']);
    } else {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $_REQUEST['i']);
    }

    $queryString .= 'c='.$coupon['code'];
}

if (isset($_REQUEST['d'])) {
    $duration = $_REQUEST['d'];
    if ($buyingCourse) {
        $subscriptionItem = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $_REQUEST['i'], $duration, $coupon);
    } else {
        $subscriptionItem = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $_REQUEST['i'], $duration, $coupon);
    }
}

if (empty($currentUserId)) {
    Session::write('buy_course_redirect', api_get_self().'?'.$queryString);
    header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php');
    exit;
}

$subscriptionItems = $plugin->getSubscriptionsItemsByProduct($_REQUEST['i'], $_REQUEST['t']);

if (!isset($subscriptionItems) || empty($subscriptionItems)) {
    api_not_allowed(true);
}

if (!isset($subscriptionItem) || empty($subscriptionItem)) {
    $subscriptionItem = $plugin->getSubscription($subscriptionItems[0]['product_type'], $subscriptionItems[0]['product_id'], $subscriptionItems[0]['duration'], $coupon);
}

$queryString .= 'd='.intval($subscriptionItem['duration']);

if ($buyingCourse) {
    $courseInfo = $plugin->getSubscriptionCourseInfo($_REQUEST['i'], $coupon);
    $item = $plugin->getSubscriptionItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
} elseif ($buyingSession) {
    $sessionInfo = $plugin->getSubscriptionSessionInfo($_REQUEST['i'], $coupon);
    $item = $plugin->getSubscriptionItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
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

    $saleId = $plugin->registerSubscriptionSale($item['product_id'], $item['product_type'], $formValues['payment_type'], $formValues['d'], $formValues['c']);

    if ($saleId !== false) {
        $_SESSION['bc_sale_id'] = $saleId;

        if (isset($formValues['c'])) {
            $couponSaleId = $plugin->registerCouponSubscriptionSale($saleId, $formValues['c']);
            if ($couponSaleId !== false) {
                $plugin->updateCouponDelivered($formValues['c']);
                $_SESSION['bc_coupon_id'] = $formValues['c'];
            }
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process_subscription_confirm.php');
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

$form->addHidden('t', intval($_GET['t']));
$form->addHidden('i', intval($_GET['i']));
if ($coupon != null) {
    $form->addHidden('c', intval($coupon['id']));
}
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success', 'btn-lg pull-right');

$formSubscription = new FormValidator('confirm_subscription');
if ($formSubscription->validate()) {
    $formSubscriptionValues = $formSubscription->getSubmitValues();

    if (!$formSubscriptionValues['duration']) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToAddDuration'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);
        exit;
    }

    if ($buyingCourse) {
        $subscription = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $_REQUEST['i'], $formSubscriptionValues['duration']);
    } else {
        $subscription = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $_REQUEST['i'], $formSubscriptionValues['duration']);
    }

    if ($subscription == null) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('SubscriptionNotValid'), 'error', false)
        );
        header('Location:'.api_get_self().'?'.$queryString);
        exit;
    }

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/subscription_process.php?i='.$_REQUEST['i'].'&t='.$_REQUEST['t'].'&d='.$formSubscriptionValues['duration']);

    exit;
}

$frequencies = $plugin->getFrequencies();
$selectedFrequencies = [];

foreach ($subscriptionItems as $si) {
    if (isset($frequencies[$si['duration']])) {
        $selectedFrequencies[$si['duration']] = $frequencies[$si['duration']].' - '.$si['price_formatted'].' '.$si['iso_code'];
    }
}

$formSubscription->addRadio('duration', null, $selectedFrequencies);

if (!empty($selectedFrequencies)) {
    $formSubscriptionDefaults['duration'] = $subscriptionItem['duration'];
    $formSubscription->setDefaults($formSubscriptionDefaults);
}

$selectedDurationName = $frequencies[$subscriptionItem['duration']];

$formSubscription->addHidden('t', intval($_GET['t']));
$formSubscription->addHidden('i', intval($_GET['i']));

$form->addHidden('d', $subscriptionItem['duration']);

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

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/subscription_process.php?i='.$_REQUEST['i'].'&t='.$_REQUEST['t'].'&d='.$_REQUEST['d'].'&c='.$formCouponValues['coupon_code']);

    exit;
}
$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', intval($_GET['t']));
$formCoupon->addHidden('i', intval($_GET['i']));
$formCoupon->addHidden('d', $subscriptionItem['duration']);
$formCoupon->addButton('submit', $plugin->get_lang('RedeemCoupon'), 'check', 'success', 'btn-lg pull-right');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = ['url' => 'subscription_course_catalog.php', 'name' => $plugin->get_lang('CourseListOnSale')];

$tpl = new Template($templateName);
$tpl->assign('item_type', (int) $_GET['t']);
$tpl->assign('buying_course', $buyingCourse);
$tpl->assign('buying_session', $buyingSession);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form_coupon', $formCoupon->returnForm());
$tpl->assign('message_payment', $messagePayment);
$tpl->assign('selected_duration_name', $selectedDurationName);
$tpl->assign('form', $form->returnForm());
$tpl->assign('form_subscription', $formSubscription->returnForm());

if ($buyingCourse) {
    $tpl->assign('course', $courseInfo);
} elseif ($buyingSession) {
    $tpl->assign('session', $sessionInfo);
}

$tpl->assign('subscription', $subscriptionItem);

$content = $tpl->fetch('buycourses/view/subscription_process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
