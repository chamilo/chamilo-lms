<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Process subscription payments for the Buy Courses plugin.
 */
require_once '../config.php';

$currentUserId = api_get_user_id();

$plugin = BuyCoursesPlugin::create();
$includeSession = 'true' === $plugin->get('include_sessions');
$paypalEnabled = 'true' === $plugin->get('paypal_enable');
$transferEnabled = 'true' === $plugin->get('transfer_enable');
$culqiEnabled = 'true' === $plugin->get('culqi_enable');
$tpvRedsysEnable = 'true' === $plugin->get('tpv_redsys_enable');

$registrationUrl = api_get_path(WEB_CODE_PATH).'auth/registration.php';
$messagePayment = '';
$coupon = null;
$subscriptionItem = null;
$courseInfo = [];
$sessionInfo = [];

$normalizeRelativePath = static function (string $url): string {
    $path = (string) parse_url($url, PHP_URL_PATH);
    $query = (string) parse_url($url, PHP_URL_QUERY);

    if ('' === $path) {
        $path = '/plugin/BuyCourses/src/subscription_process.php';
    }

    return '' !== $query ? $path.'?'.$query : $path;
};

if (!$paypalEnabled && !$transferEnabled && !$culqiEnabled && !$tpvRedsysEnable) {
    api_not_allowed(true);
}

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    api_not_allowed(true);
}

$productType = (int) $_REQUEST['t'];
$productId = (int) $_REQUEST['i'];
$buyingCourse = BuyCoursesPlugin::PRODUCT_TYPE_COURSE === $productType;
$buyingSession = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === $productType;

if (!$buyingCourse && !$buyingSession) {
    api_not_allowed(true);
}

if ($buyingSession && !$includeSession) {
    api_not_allowed(true);
}

$queryString = 'i='.$productId.'&t='.$productType;

if (isset($_REQUEST['c']) && '' !== trim((string) $_REQUEST['c'])) {
    $couponCode = trim((string) $_REQUEST['c']);

    if ($buyingCourse) {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $productId);
    } else {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $productId);
    }

    if (!empty($coupon) && !empty($coupon['code'])) {
        $queryString .= '&c='.urlencode((string) $coupon['code']);
    }
}

if (isset($_REQUEST['d']) && '' !== trim((string) $_REQUEST['d'])) {
    $duration = (int) $_REQUEST['d'];

    if ($duration > 0) {
        if ($buyingCourse) {
            $subscriptionItem = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $productId, $duration, $coupon);
        } else {
            $subscriptionItem = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $productId, $duration, $coupon);
        }
    }
}

if (empty($currentUserId)) {
    $currentPath = $normalizeRelativePath(
        (string) ($_SERVER['REQUEST_URI'] ?? (api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_process.php?'.$queryString))
    );

    Session::write('buy_course_redirect', $currentPath);
    header('Location: '.$registrationUrl);
    exit;
}

$subscriptionItems = $plugin->getSubscriptionsItemsByProduct($productId, $productType);

if (empty($subscriptionItems)) {
    api_not_allowed(true);
}

if (empty($subscriptionItem)) {
    $subscriptionItem = $plugin->getSubscription(
        (int) $subscriptionItems[0]['product_type'],
        (int) $subscriptionItems[0]['product_id'],
        (int) $subscriptionItems[0]['duration'],
        $coupon
    );
}

$queryString .= '&d='.(int) $subscriptionItem['duration'];

if ($buyingCourse) {
    $courseInfo = $plugin->getSubscriptionCourseInfo($productId, $coupon);
    $item = $plugin->getSubscriptionItemByProduct($productId, BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
} else {
    $sessionInfo = $plugin->getSubscriptionSessionInfo($productId, $coupon);
    $item = $plugin->getSubscriptionItemByProduct($productId, BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
}

$form = new FormValidator('confirm_sale');
if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $paymentType = isset($formValues['payment_type']) ? (int) $formValues['payment_type'] : 0;
    $selectedDuration = isset($formValues['d']) ? (int) $formValues['d'] : 0;
    $submittedCouponId = isset($formValues['c']) ? (int) $formValues['c'] : null;

    if ($paymentType <= 0) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToSelectPaymentType'), 'error', false)
        );
        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    $saleId = $plugin->registerSubscriptionSale(
        (int) $item['product_id'],
        (int) $item['product_type'],
        $paymentType,
        $selectedDuration,
        $submittedCouponId ?? 0
    );

    if (false !== $saleId) {
        $_SESSION['bc_sale_id'] = $saleId;

        if (null !== $submittedCouponId && $submittedCouponId > 0) {
            $couponSaleId = $plugin->registerCouponSubscriptionSale($saleId, $submittedCouponId);
            if (false !== $couponSaleId) {
                $plugin->updateCouponDelivered($submittedCouponId);
                $_SESSION['bc_coupon_id'] = $submittedCouponId;
            }
        }

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/process_subscription_confirm.php');
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
$form->addHidden('d', (int) $subscriptionItem['duration']);
if (null !== $coupon && isset($coupon['id'])) {
    $form->addHidden('c', (int) $coupon['id']);
}
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success', 'btn-lg pull-right');

$formSubscription = new FormValidator('confirm_subscription');
if ($formSubscription->validate()) {
    $formSubscriptionValues = $formSubscription->getSubmitValues();
    $selectedDuration = isset($formSubscriptionValues['duration']) ? (int) $formSubscriptionValues['duration'] : 0;

    if ($selectedDuration <= 0) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('NeedToAddDuration'), 'error', false)
        );
        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    if ($buyingCourse) {
        $subscription = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $productId, $selectedDuration);
    } else {
        $subscription = $plugin->getSubscription(BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $productId, $selectedDuration);
    }

    if (null == $subscription) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('SubscriptionNotValid'), 'error', false)
        );
        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    header(
        'Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_process.php?i='.
        $productId.'&t='.$productType.'&d='.$selectedDuration
    );
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

$formSubscription->addHidden('t', $productType);
$formSubscription->addHidden('i', $productId);

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
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_COURSE, $productId);
    } else {
        $coupon = $plugin->getCouponByCode($couponCode, BuyCoursesPlugin::PRODUCT_TYPE_SESSION, $productId);
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
        'Location: '.api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscription_process.php?i='.
        $productId.'&t='.$productType.'&d='.(int) $subscriptionItem['duration'].'&c='.
        urlencode((string) $coupon['code'])
    );
    exit;
}
$formCoupon->addText('coupon_code', $plugin->get_lang('CouponsCode'), true);
$formCoupon->addHidden('t', $productType);
$formCoupon->addHidden('i', $productId);
$formCoupon->addHidden('d', (int) $subscriptionItem['duration']);
$formCoupon->addButton('submit', $plugin->get_lang('RedeemCoupon'), 'check', 'success', 'btn-lg pull-right');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = [
    'url' => $buyingCourse ? 'subscription_course_catalog.php' : 'subscription_session_catalog.php',
    'name' => $plugin->get_lang('CourseListOnSale'),
];

$tpl = new Template($templateName);
$tpl->assign('item_type', $productType);
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

$content = $tpl->fetch('BuyCourses/view/subscription_process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
