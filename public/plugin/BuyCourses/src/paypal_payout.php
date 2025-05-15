<?php
/* For license terms, see /license.txt */

/**
 * List page for Paypal Payout for the Buy Courses plugin.
 */
/**
 * Initialization.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$htmlHeadXtra[] = '<link rel="stylesheet" href="../resources/css/style.css" type="text/css">';

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');

if ("true" !== $paypalEnable && "true" !== $commissionsEnable) {
    api_not_allowed(true);
}

$payouts = $plugin->getPayouts();

$payoutList = [];

foreach ($payouts as $payout) {
    $payoutList[] = [
        'id' => $payout['id'],
        'reference' => $payout['sale_reference'],
        'date' => api_format_date($payout['date'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $payout['iso_code'],
        'price' => $payout['item_price'],
        'commission' => $payout['commission'],
        'paypal_account' => $payout['paypal_account'],
    ];
}

$templateName = $plugin->get_lang('PaypalPayoutCommissions');

$template = new Template($templateName);

$template->assign('payout_list', $payoutList);

$content = $template->fetch('BuyCourses/view/paypal_payout.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
