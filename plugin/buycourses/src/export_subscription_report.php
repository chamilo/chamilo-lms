<?php
/* For license terms, see /license.txt */
//Initialization
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();
$form = new FormValidator('export_validate');

$form->addDatePicker('date_start', get_lang('DateStart'), false);
$form->addDatePicker('date_end', get_lang('DateEnd'), false);
$form->addButton('export_sales', get_lang('ExportExcel'), 'check', 'primary');
$salesStatus = [];

if ($form->validate()) {
    $reportValues = $form->getSubmitValues();

    $dateStart = $reportValues['date_start'];
    $dateEnd = $reportValues['date_end'];

    if ($dateStart == null || $dateEnd == null) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('SelectDateRange'), 'error', false)
        );
    } elseif ($dateStart > $dateEnd) {
        Display::addFlash(
            Display::return_message(get_lang('EndDateCannotBeBeforeTheStartDate'), 'error', false)
        );
    } else {
        $salesStatus = $plugin->getSubscriptionSaleListReport($dateStart, $dateEnd);
    }
}

if (!empty($salesStatus)) {
    $archiveFile = 'export_report_sales_'.api_get_local_time();
    Export::arrayToXls($salesStatus, $archiveFile);
}
$interbreadcrumb[] = [
    'url' => '../index.php', 'name' => $plugin->get_lang('plugin_title'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/src/subscription_sales_report.php',
    'name' => $plugin->get_lang('SubscriptionSalesReport'),
];

$templateName = $plugin->get_lang('ExportReport');
$toolbar = Display::url(
    Display::return_icon('back.png', get_lang('GoBack'), [], ICON_SIZE_MEDIUM),
    api_get_path(WEB_PLUGIN_PATH).'buycourses/src/subscription_sales_report.php'
);
$template = new Template($templateName);
$template->assign(
    'actions',
    Display::toolbarAction('toolbar', [$toolbar])
);
$template->assign('form', $form->returnForm());
$content = $template->fetch('buycourses/view/export_report.tpl');
$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
