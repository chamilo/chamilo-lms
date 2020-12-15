<?php

/* For license terms, see /license.txt */
/**
 * List of pending payments of the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
//Initialization
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');
$htmlHeadXtra[] =
    '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/1.0.2/Chart.min.js"></script>';

api_protect_admin_script(true);

$plugin = BuyCoursesPlugin::create();

$commissionsEnable = $plugin->get('commissions_enable');
$payoutStatuses = $plugin->getPayoutStatuses();
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : BuyCoursesPlugin::SALE_STATUS_COMPLETED;

if ($commissionsEnable !== "true") {
    api_not_allowed(true);
}

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedStatus = $form->getSubmitValue('status');

    if ($selectedStatus === false) {
        $selectedStatus = BuyCoursesPlugin::PAYOUT_STATUS_PENDING;
    }
}

$form->addSelect('status', $plugin->get_lang('PayoutStatus'), $payoutStatuses);
$form->addButtonFilter(get_lang('Search'));
$form->setDefaults([
    'status' => $selectedStatus,
]);

switch ($selectedStatus) {
    case '2':
        $payouts = $plugin->getPayouts($selectedStatus);

        break;
    case '1':
        $payouts = $plugin->getPayouts($selectedStatus);

        break;
    case '0':
    default:
        $payouts = $plugin->getPayouts();

        break;
}

$payoutList = [];

foreach ($payouts as $payout) {
    $payoutList[] = [
        'id' => $payout['id'],
        'sale_id' => $payout['sale_id'],
        'reference' => $payout['sale_reference'],
        'date' => api_format_date($payout['date'], DATE_TIME_FORMAT_LONG_24H),
        'payout_date' => ($payout['payout_date'] === '0000-00-00 00:00:00')
            ? '-'
            : api_format_date($payout['payout_date'], DATE_TIME_FORMAT_LONG_24H),
        'currency' => $payout['iso_code'],
        'price' => $payout['item_price'],
        'commission' => $payout['commission'],
        'beneficiary' => api_get_person_name($payout['firstname'], $payout['lastname']),
        'paypal_account' => $payout['paypal_account'],
        'status' => $payout['status'],
    ];
}

$templateName = $plugin->get_lang('PayoutReport');

$template = new Template($templateName);

$template->assign('form', $form->returnForm());
$template->assign('payout_list', $payoutList);
$template->assign('selected_status', $selectedStatus);

$content = $template->fetch('buycourses/view/payout_report.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
