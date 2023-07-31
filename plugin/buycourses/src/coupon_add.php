<?php
/* For license terms, see /license.txt */

/**
 * Configuration script for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

api_protect_admin_script();

$plugin = BuyCoursesPlugin::create();

$includeSession = $plugin->get('include_sessions') === 'true';
$includeServices = $plugin->get('include_services') === 'true';

$entityManager = Database::getManager();
$userRepo = UserManager::getRepository();
$currency = $plugin->getSelectedCurrency();

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('CurrencyIsNotConfigured'), 'error')
    );
}

$currencyIso = null;

$coursesList = CourseManager::get_courses_list(
    0,
    0,
    'title',
    'asc',
    -1,
    null,
    api_get_current_access_url_id(),
    false,
    [],
    []
);

foreach ($coursesList as $course) {
    $courses[$course['id']] = $course['title'];
}

$sessionsList = SessionManager::get_sessions_list(
    [],
    [],
    null,
    null,
    api_get_current_access_url_id(),
    []
);

foreach ($sessionsList as $session) {
    $sessions[$session['id']] = $session['name'];
}

$servicesList = $plugin->getAllServices();

foreach ($servicesList as $service) {
    $services[$service['id']] = $service['name'];
}

$discountTypes = $plugin->getCouponDiscountTypes();

// Build the form
$form = new FormValidator('add_coupon');
$form->addText('code', $plugin->get_lang('CouponCode'), true);
$form->addRadio('discount_type', $plugin->get_lang('CouponDiscountType'), $discountTypes);
$form->addElement(
    'number',
    'discount_amount',
    [$plugin->get_lang('CouponDiscount'), null, $currencyIso],
    ['step' => 1]
);
$form->addDateRangePicker('date', get_lang('Date'), true);
$form->addCheckBox('active', get_lang('Active'));
$form->addElement(
    'advmultiselect',
    'courses',
    get_lang('Courses'),
    $courses
);

if ($includeSession) {
    $form->addElement(
        'advmultiselect',
        'sessions',
        get_lang('Sessions'),
        $sessions
    );
}

if ($includeServices) {
    $form->addElement(
        'advmultiselect',
        'services',
        get_lang('Services'),
        $services
    );
}

$button = $form->addButtonSave(get_lang('Save'));

if (empty($currency)) {
    $button->setAttribute('disabled');
}

if ($form->validate()) {
    $formValues = $form->exportValues();

    $coupon['code'] = $formValues['code'];
    $coupon['discount_type'] = $formValues['discount_type'];
    $coupon['discount_amount'] = $formValues['discount_amount'];
    $coupon['valid_start'] = $formValues['date_start'];
    $coupon['valid_end'] = $formValues['date_end'];
    $coupon['active'] = $formValues['active'];

    if ($coupon['discount_type'] == BuyCoursesPlugin::COUPON_DISCOUNT_TYPE_PERCENTAGE && $coupon['discount_amount'] > 100) {
        Display::addFlash(
            Display::return_message($plugin->get_lang('CouponDiscountExceed100'), 'error', false)
        );
    }

    $coupon['courses'] = isset($formValues['courses']) ? $formValues['courses'] : [];
    $coupon['sessions'] = isset($formValues['sessions']) ? $formValues['sessions'] : [];
    $coupon['services'] = isset($formValues['services']) ? $formValues['services'] : [];

    $result = $plugin->addNewCoupon($coupon);

    if ($result) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/coupons.php');
    } else {
        header('Location:'.api_get_self().'?'.$queryString);
    }

    exit;
}

$formDefaults = [
    'code' => '',
    'discount_type' => null,
    'discount_amount' => 0,
    'active' => 0,
    'courses' => [],
    'sessions' => [],
    'services' => [],
];

$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('CouponAdd');
$interbreadcrumb[] = [
    'url' => 'paymentsetup.php',
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => 'coupons.php',
    'name' => $plugin->get_lang('CouponList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('content', $form->returnForm());
$template->display_one_col_template();
