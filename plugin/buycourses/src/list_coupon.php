<?php
/* For license terms, see /license.txt */

/**
 * List of couponsof the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

if (isset($_GET['coupon_id'])) {
    $coupon = $plugin->getCouponInfo($_GET['coupon_id']);

    if (empty($coupon)) {
        api_not_allowed(true);
    }

    $urlToRedirect = api_get_self().'?';

    switch ($_GET['action']) {
        case 'deactivate':
            //activate coupon
            break;
        case 'activate':
            //deactivate coupon
            break;
    }

    header("Location: $urlToRedirect");
    exit;
}

$discountTypes = $plugin->getCouponDiscountTypes();
$couponStatuses = $plugin->getCouponStatuses();

$selectedFilterType = '0';
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : BuyCoursesPlugin::COUPON_STATUS_ACTIVE;

$form = new FormValidator('search', 'get');

if ($form->validate()) {
    $selectedStatus = $form->getSubmitValue('status');

    if ($selectedStatus === false) {
        $selectedStatus = BuyCoursesPlugin::COUPON_STATUS_ACTIVE;
    }

    if ($selectedFilterType === false) {
        $selectedFilterType = '0';
    }
}

$form->addHtml('<div id="report-by-status" '.($selectedFilterType !== '0' ? 'style="display:none"' : '').'>');
$form->addSelect('status', $plugin->get_lang('CouponStatus'), $couponStatuses);
$form->addHtml('</div>');
$form->addButtonFilter(get_lang('Search'));
$form->setDefaults([
    'filter_type' => $selectedFilterType,
    'status' => $selectedStatus,
]);

$coupons = $plugin->getCouponsListByStatus($selectedStatus);

foreach ($coupons as &$coupon) {
    if ($coupon['discount_type'] == BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE) {
        $coupon['discount_value'] = $coupon['discount_amount']." %";
    } elseif ($coupon['discount_type'] == BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_AMOUNT) {
        $coupon['discount_value'] = $plugin->getPriceWithCurrencyFromIsoCode($coupon['discount_amount'], $coupon['iso_code']);
    }
    $coupon['discount_type'] = $discountTypes[$coupon['discount_type']];
}

$interbreadcrumb[] = ['url' => '../index.php', 'name' => $plugin->get_lang('plugin_title')];

$htmlHeadXtra[] = api_get_css(api_get_path(WEB_PLUGIN_PATH).'buycourses/resources/css/style.css');

$templateName = $plugin->get_lang('CouponList');
$template = new Template($templateName);

$template->assign('form', $form->returnForm());
$template->assign('selected_status', $selectedStatus);
$template->assign('coupon_list', $coupons);
$template->assign('coupon_status_active', BuyCoursesPlugin::COUPON_STATUS_ACTIVE);
$template->assign('coupon_status_disable', BuyCoursesPlugin::COUPON_STATUS_DISABLE);

$content = $template->fetch('buycourses/view/list_coupon.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);
$template->display_one_col_template();
