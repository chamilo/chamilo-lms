<?php
/* For license terms, see /license.txt */

/**
 * Configuration script for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
require_once '../config.php';

api_protect_admin_script();

$couponId = $_REQUEST['id'];

if (!isset($couponId)) {
    api_not_allowed();
}

$plugin = BuyCoursesPlugin::create();

$coupon = $plugin->getCouponInfo($couponId);

if (!isset($coupon)) {
    api_not_allowed();
}

$couponDateRangeFrom = $coupon['valid_start'];
$couponDateRangeTo = $coupon['valid_end'];

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
$form->addText('code', $plugin->get_lang('CouponCode'), false);
$form->addText('discount_type', $plugin->get_lang('CouponDiscountType'), false);
$form->addText('discount_amount', $plugin->get_lang('CouponDiscount'), false);
$form->addDateRangePicker(
    'date',
    get_lang('Date'),
    true,
    [
        'value' => "$couponDateRangeFrom / $couponDateRangeTo",
    ]
);

$form->addCheckBox('active', $plugin->get_lang('CouponActive'));
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

$form->addHidden('id', null);

$coursesAdded = $coupon["courses"];
if (!empty($coursesAdded)) {
    $coursesAdded = array_column($coursesAdded, 'id');
}

$sessionsAdded = $coupon["sessions"];
if (!empty($sessionsAdded)) {
    $sessionsAdded = array_column($sessionsAdded, 'id');
}

$servicesAdded = $coupon["services"];
if (!empty($servicesAdded)) {
    $servicesAdded = array_column($servicesAdded, 'id');
}

$formDefaults = [
    'id' => $coupon['id'],
    'code' => $coupon['code'],
    'discount_type' => $discountTypes[$coupon['discount_type']],
    'discount_amount' => $coupon['discount_amount'],
    'date' => "$couponDateRangeFrom / $couponDateRangeTo",
    'active' => $coupon['active'],
    'courses' => $coursesAdded,
    'sessions' => $sessionsAdded,
    'services' => $servicesAdded,
];

$button = $form->addButtonSave(get_lang('Save'));
if (empty($currency)) {
    $button->setAttribute('disabled');
}

$form->freeze(['code', 'discount_type', 'discount_amount']);

if ($form->validate()) {
    $formValues = $form->exportValues();

    $coupon['id'] = $formValues['id'];
    $coupon['valid_start'] = $formValues['date_start'];
    $coupon['valid_end'] = $formValues['date_end'];
    $coupon['active'] = $formValues['active'];
    $coupon['courses'] = isset($formValues['courses']) ? $formValues['courses'] : [];
    $coupon['sessions'] = isset($formValues['sessions']) ? $formValues['sessions'] : [];
    $coupon['services'] = isset($formValues['services']) ? $formValues['services'] : [];

    $result = $plugin->updateCouponData($coupon);

    if ($result) {
        Display::addFlash(
            Display::return_message(
                $plugin->get_lang('CouponUpdate'),
                'success',
                false
            )
        );

        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/configure_coupon.php?id='.$coupon["id"]);
    } else {
        header('Location:'.api_get_self().'?'.$queryString);
    }

    exit;
}

$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('ConfigureCoupon');
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
