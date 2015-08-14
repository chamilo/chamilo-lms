<?php
/* For license terms, see /license.txt */
/**
 * Configuration page for payment methods for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization
 */
require_once dirname(__FILE__) . '/buy_course.lib.php';
require_once '../../../main/inc/global.inc.php';
require_once 'buy_course_plugin.class.php';

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();
BuyCoursesUtils::setPlugin($plugin);

$paypalEnable = $plugin->get('paypal_enable');
$transferEnable = $plugin->get('transfer_enable');

if (isset($_GET['action'], $_GET['id'])) {
    if ($_GET['action'] == 'delete_taccount') {
        $plugin->deleteTransferAccount($_GET['id']);

        Display::addFlash(
            Display::return_message(get_lang('ItemRemoved'), 'success')
        );

        header('Location: ' . api_get_self());
        exit;
    }
}

$currencyForm = BuyCoursesUtils::getCurrencyForm();
$paypalForm = BuyCoursesUtils::getPaypalForm();
$transferForm = BuyCoursesUtils::getTransferForm();
$transferAccounts = $plugin->getTransferAccounts();

//view
$interbreadcrumb[] = [
    'url' => 'list.php',
    'name' => $plugin->get_lang('CourseListOnSale')
];
$interbreadcrumb[] = [
    'url' => 'configuration.php',
    'name' => $plugin->get_lang('AvailableCoursesConfiguration')
];

$tpl = new Template(
    $plugin->get_lang('PaymentConfiguration')
);
$tpl->assign(
    'header',
    $plugin->get_lang('PaymentConfiguration')
);
$tpl->assign('server', $_configuration['root_web']);
$tpl->assign('curency_form', $currencyForm->returnForm());
$tpl->assign('paypal_form', $paypalForm->returnForm());
$tpl->assign('transfer_form', $transferForm->returnForm());
$tpl->assign('transfer_accounts', $transferAccounts);
$tpl->assign('paypal_enable', $paypalEnable);
$tpl->assign('transfer_enable', $transferEnable);

$content = $tpl->fetch('buycourses/view/paymentsetup.tpl');

$tpl->assign('content', $content);
$tpl->display_one_col_template();
