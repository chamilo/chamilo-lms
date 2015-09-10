<?php
/* For license terms, see /license.txt */
/**
 * List of pending payments of the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */
//Initialization
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

if (isset($_GET['order'])) {
    $sale = $plugin->getSale($_GET['order']);

    if (empty($sale)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self() . '?';

    switch ($_GET['action']) {
        case 'confirm':
            $plugin->completeSale($sale['id']);

            Display::addFlash(
                Display::return_message(
                    sprintf($plugin->get_lang('SubscriptionToCourseXSuccessful'), $sale['product_name']),
                    'success'
                )
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SALE_STATUS_COMPLETED,
                'sale' =>  $sale['id']
            ]);
            break;
        case 'cancel':
            $plugin->cancelSale($sale['id']);

            Display::addFlash(
                Display::return_message(
                    $plugin->get_lang('OrderCanceled'),
                    'warning'
                )
            );

            $urlToRedirect .= http_build_query([
                'status' => BuyCoursesPlugin::SALE_STATUS_CANCELED,
                'sale' =>  $sale['id']
            ]);
            break;
    }

    header("Location: $urlToRedirect");
    exit;
}

$productTypes = $plugin->getProductTypes();
$saleStatuses = $plugin->getSaleStatuses();
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : BuyCoursesPlugin::SALE_STATUS_PENDING;
$selectedSale = isset($_GET['sale']) ? intval($_GET['sale']) : 0;

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedStatus = $form->getSubmitValue('status');

    if ($selectedStatus === false) {
        $selectedStatus = BuyCoursesPlugin::SALE_STATUS_PENDING;
    }
}

$form->addSelect('status', $plugin->get_lang('OrderStatus'), $saleStatuses);
$form->addButtonFilter($plugin->get_lang('SearchByStatus'));
$form->setDefaults(['status' => $selectedStatus]);

$sales = $plugin->getSaleListByStatus($selectedStatus);
$saleList = [];

foreach ($sales as $sale) {
    $saleList[] = [
        'id' => $sale['id'],
        'reference' => $sale['reference'],
        'status' => $sale['status'],
        'date' => api_format_date($sale['date'], DATE_FORMAT_LONG_NO_DAY),
        'currency' => $sale['iso_code'],
        'price' => $sale['price'],
        'product_name' => $sale['product_name'],
        'product_type' => $productTypes[$sale['product_type']],
        'complete_user_name' => api_get_person_name($sale['firstname'], $sale['lastname'])
    ];
}

//View
$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$templateName = $plugin->get_lang('SalesReport');

$template = new Template($templateName);
$template->assign('form', $form->returnForm());
$template->assign('selected_sale', $selectedSale);
$template->assign('selected_status', $selectedStatus);
$template->assign('sale_list', $saleList);
$template->assign('sale_status_canceled', BuyCoursesPlugin::SALE_STATUS_CANCELED);
$template->assign('sale_status_pending', BuyCoursesPlugin::SALE_STATUS_PENDING);
$template->assign('sale_status_completed', BuyCoursesPlugin::SALE_STATUS_COMPLETED);

$content = $template->fetch('buycourses/view/sales_report.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
