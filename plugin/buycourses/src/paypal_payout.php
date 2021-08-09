<?php
/* For license terms, see /license.txt */

/**
 * List page for Paypal Payout for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
/**
 * Initialization.
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();

$paypalEnable = $plugin->get('paypal_enable');
$commissionsEnable = $plugin->get('commissions_enable');

if ($paypalEnable !== "true" && $commissionsEnable !== "true") {
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

$content = $template->fetch('buycourses/view/paypal_payout.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
