<?php
/* For license terms, see /license.txt */
/**
 * Process payments for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
require_once '../config.php';
require_once dirname(__FILE__) . '/buy_course.lib.php';

$currentUserId = api_get_user_id();

if (empty($currentUserId)) {
    header('Location: ' . api_get_path(WEB_CODE_PATH) . 'auth/inscription.php');
    exit;
}

$plugin = BuyCoursesPlugin::create();
$includeSession = $plugin->get('include_sessions') === 'true';
$paypalEnabled = $plugin->get('paypal_enable') === 'true';
$transferEnabled = $plugin->get('transfer_enable') === 'true';

if (!isset($_REQUEST['t'], $_REQUEST['i'])) {
    die;
}

$buyingCourse = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$buyingSession = intval($_REQUEST['t']) === BuyCoursesPlugin::PRODUCT_TYPE_SESSION;

if ($buyingCourse) {
    $courseInfo = $plugin->getCourseInfo($_REQUEST['i']);
    $item = $plugin->getItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_COURSE);
} elseif ($buyingSession) {
    $sessionInfo = $plugin->getSessionInfo($_REQUEST['i']);
    $item = $plugin->getItemByProduct($_REQUEST['i'], BuyCoursesPlugin::PRODUCT_TYPE_SESSION);
}

$userInfo = api_get_user_info();

$form = new FormValidator('confirm_sale');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    $saleId = $plugin->registerSale($item['id'], $formValues['payment_type']);

    if ($saleId !== false) {
        $_SESSION['bc_sale_id'] = $saleId;
        header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'buycourses/src/process_confirm.php');  
    }

    exit;
}

$form->addHeader($plugin->get_lang('UserInformation'));
$form->addText('name', get_lang('Name'), false, ['cols-size' => [5, 7, 0]]);
$form->addText('username', get_lang('Username'), false, ['cols-size' => [5, 7, 0]]);
$form->addText('email', get_lang('EmailAddress'), false, ['cols-size' => [5, 7, 0]]);
$form->addHeader($plugin->get_lang('PaymentMethods'));
$form->addRadio(
    'payment_type',
    null,
    $plugin->getPaymentTypes()
);
$form->addHidden('t', intval($_GET['t']));
$form->addHidden('i', intval($_GET['i']));
$form->freeze(['name', 'username', 'email']);
$form->setDefaults([
    'name' => $userInfo['complete_name'],
    'username' => $userInfo['username'],
    'email' => $userInfo['email']
]);
$form->addButton('submit', $plugin->get_lang('ConfirmOrder'), 'check', 'success');

// View
$templateName = $plugin->get_lang('PaymentMethods');
$interbreadcrumb[] = array("url" => "course_catalog.php", "name" => $plugin->get_lang('CourseListOnSale'));

$tpl = new Template($templateName);
$tpl->assign('buying_course', $buyingCourse);
$tpl->assign('buying_session', $buyingSession);
$tpl->assign('user', api_get_user_info());
$tpl->assign('paypal_enabled', $paypalEnabled);
$tpl->assign('transfer_enabled', $transferEnabled);
$tpl->assign('form', $form->returnForm());

if ($buyingCourse) {
    $tpl->assign('course', $courseInfo);
} elseif ($buyingSession) {
    $tpl->assign('session', $sessionInfo);
}

$content = $tpl->fetch('buycourses/view/process.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
