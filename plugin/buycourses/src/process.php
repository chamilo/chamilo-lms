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

if (empty($currentUserId)) {
    Session::write('buy_course_redirect', api_get_self().'?'.$queryString);
    header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php');
    exit;
}

if ($buyingCourse) {
    $courseInfo = $plugin->getCourseInfo($_REQUEST['i']);
    $item = $plugin->getItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
} elseif ($buyingSession) {
    $sessionInfo = $plugin->getSessionInfo($_REQUEST['i']);
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

    $saleId = $plugin->registerSale($item['id'], $formValues['payment_type']);

    if ($saleId !== false) {
        $_SESSION['bc_sale_id'] = $saleId;
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/process_confirm.php');
    }

    exit;
}

$paymentTypesOptions = $plugin->getPaymentTypes();

if (!$paypalEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_PAYPAL]);
}

if (!$transferEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_TRANSFER]);
}

if (!$culqiEnabled) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_CULQI]);
}

if (!$tpvRedsysEnable || !file_exists(api_get_path(SYS_PLUGIN_PATH).'buycourses/resources/apiRedsys.php')) {
    unset($paymentTypesOptions[BuyCoursesPlugin::PAYMENT_TYPE_TPV_REDSYS]);
}

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
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success', 'btn-lg pull-right');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = ['url' => 'course_catalog.php', 'name' => $plugin->get_lang('CourseListOnSale')];

$tpl = new Template($templateName);
$tpl->assign('item_type', (int) $_GET['t']);
$tpl->assign('buying_course', $buyingCourse);
$tpl->assign('buying_session', $buyingSession);
$tpl->assign('user', api_get_user_info());
$tpl->assign('form', $form->returnForm());

if ($buyingCourse) {
    $tpl->assign('course', $courseInfo);
} elseif ($buyingSession) {
    $tpl->assign('session', $sessionInfo);
}

$content = $tpl->fetch('buycourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
