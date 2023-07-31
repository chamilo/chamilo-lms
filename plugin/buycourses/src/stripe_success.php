<?php
/* For license terms, see /license.txt */

/**
 * Success page for the purchase of a course in the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

$plugin = BuyCoursesPlugin::create();
$stripeEnabled = $plugin->get('stripe_enable') === 'true';

if (!$stripeEnabled) {
    api_not_allowed(true);
}

$sale = $plugin->getSale($_SESSION['bc_sale_id']);

if (empty($sale)) {
    api_not_allowed(true);
}

$userInfo = api_get_user_info($sale['user_id']);
$currency = $plugin->getCurrency($sale['currency_id']);
$globalParameters = $plugin->getGlobalParameters();

if (!empty($globalParameters['sale_email'])) {

    $messageConfirmBuyerTemplate = new Template();
    $messageConfirmBuyerTemplate->assign('user', $userInfo);
    $messageConfirmBuyerTemplate->assign(
        'sale',
        [
            'date' => $sale['date'],
            'product' => $sale['product_name'],
            'currency' => $currency['iso_code'],
            'price' => $sale['price'],
            'reference' => $sale['reference'],
        ]
    );

    api_mail_html(
        $userInfo['complete_name'],
        $userInfo['email'],
        $plugin->get_lang('bc_subject'),
        $messageConfirmBuyerTemplate->fetch('buycourses/view/message_confirm_buyer.tpl'),
        '',
        $globalParameters['sale_email'],
    );

    $messageConfirmTemplate = new Template();
    $messageConfirmTemplate->assign('user', $userInfo);
    $messageConfirmTemplate->assign(
        'sale',
        [
            'date' => $sale['date'],
            'product' => $sale['product_name'],
            'currency' => $currency['iso_code'],
            'price' => $sale['price'],
            'reference' => $sale['reference'],
        ]
    );

    api_mail_html(
        '',
        $globalParameters['sale_email'],
        $plugin->get_lang('bc_subject'),
        $messageConfirmTemplate->fetch('buycourses/view/message_confirm.tpl')
    );
}

Display::addFlash(
    $plugin->getSubscriptionSuccessMessage($sale)
);

unset($_SESSION['bc_sale_id']);
header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/course_catalog.php');
exit;
