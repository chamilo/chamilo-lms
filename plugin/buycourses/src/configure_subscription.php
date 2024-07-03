<?php
/* For license terms, see /license.txt */

/**
 * Configuration page for subscriptions for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script(true);

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : 0;

if (!isset($id) || !isset($type)) {
    api_not_allowed();
}

$queryString = 'id='.intval($_REQUEST['id']).'&type='.intval($_REQUEST['type']);

$editingCourse = $type === BuyCoursesPlugin::PRODUCT_TYPE_COURSE;
$editingSession = $type === BuyCoursesPlugin::PRODUCT_TYPE_SESSION;

$plugin = BuyCoursesPlugin::create();

$includeSession = $plugin->get('include_sessions') === 'true';

if (isset($_GET['action'], $_GET['d'])) {
    if ($_GET['action'] == 'delete_frequency') {
        $plugin->deleteSubscription($type, $id, $_GET['d']);

        Display::addFlash(
            Display::return_message(get_lang('ItemRemoved'), 'success')
        );

        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }
}

$entityManager = Database::getManager();
$userRepo = UserManager::getRepository();
$currency = $plugin->getSelectedCurrency();

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('CurrencyIsNotConfigured'), 'error')
    );
}

$subscriptions = $plugin->getSubscriptions($type, $id);

$taxtPerc = 0;

if (isset($subscriptions) && !empty($subscriptions)) {
    $taxtPerc = $subscriptions[0]['tax_perc'];
}

$currencyIso = null;

if ($editingCourse) {
    $course = $entityManager->find('ChamiloCoreBundle:Course', $id);
    if (!$course) {
        api_not_allowed(true);
    }

    $courseItem = $plugin->getCourseForConfiguration($course, $currency);

    $currencyIso = $courseItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Course'),
        'id' => $courseItem['course_id'],
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_COURSE,
        'name' => $courseItem['course_title'],
        'visible' => $courseItem['visible'],
        'tax_perc' => $taxtPerc,
    ];
} elseif ($editingSession) {
    if (!$includeSession) {
        api_not_allowed(true);
    }

    $session = $entityManager->find('ChamiloCoreBundle:Session', $id);
    if (!$session) {
        api_not_allowed(true);
    }

    $sessionItem = $plugin->getSessionForConfiguration($session, $currency);

    $currencyIso = $sessionItem['currency'];
    $formDefaults = [
        'product_type' => get_lang('Session'),
        'id' => $session->getId(),
        'type' => BuyCoursesPlugin::PRODUCT_TYPE_SESSION,
        'name' => $sessionItem['session_name'],
        'visible' => $sessionItem['visible'],
        'tax_perc' => $taxtPerc,
    ];
} else {
    api_not_allowed(true);
}

$globalSettingsParams = $plugin->getGlobalParameters();

$form = new FormValidator('add_subscription');

$form->addText('product_type', $plugin->get_lang('ProductType'), false);
$form->addText('name', get_lang('Name'), false);

$form->freeze(['product_type', 'name']);

$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    ['step' => 1, 'placeholder' => $globalSettingsParams['global_tax_perc'].'% '.$plugin->get_lang('ByDefault')]
);

$frequenciesOptions = $plugin->getFrequencies();

$frequencyForm = new FormValidator('frequency_config', 'post', api_get_self().'?'.$queryString);

$frequencyFormDefaults = [
    'id' => $id,
    'type' => $type,
    'tax_perc' => $taxtPerc,
    'currency_id' => $currency['id'],
];

$frequencyForm->setDefaults($frequencyFormDefaults);

if ($frequencyForm->validate()) {
    $frequencyFormValues = $frequencyForm->getSubmitValues();

    $subscription['product_id'] = $frequencyFormValues['id'];
    $subscription['product_type'] = $frequencyFormValues['type'];
    $subscription['tax_perc'] = $frequencyFormValues['tax_perc'] != '' ? (int) $frequencyFormValues['tax_perc'] : null;
    $subscription['currency_id'] = $currency['id'];
    $duration = $frequencyFormValues['duration'];
    $price = $frequencyFormValues['price'];

    for ($i = 0; $i <= count($subscriptions); $i++) {
        if ($subscriptions[$i]['duration'] == $duration) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('SubscriptionAlreadyExists'), 'error')
            );

            header('Location:'.api_get_self().'?'.$queryString);
            exit;
        }
    }

    $subscription['frequencies'] = [['duration' => $duration, 'price' => $price]];

    $result = $plugin->addNewSubscription($subscription);

    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'success')
    );

    header('Location:'.api_get_self().'?'.$queryString);
    exit;
}

$frequencyForm->addElement(
    'select',
    'duration',
    $plugin->get_lang('Duration'),
    $frequenciesOptions,
    ['cols-size' => [2, 8, 2]]
);

$frequencyForm->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currencyIso],
    false,
    [
        'step' => 1,
        'cols-size' => [3, 8, 1],
    ]
);

$frequencyForm->addHidden('type', $type);
$frequencyForm->addHidden('id', $id);
$frequencyForm->addHidden('tax_perc', $taxtPerc);
$frequencyForm->addHidden('currency_id', $currency['id']);
$frequencyForm->addButtonCreate('Add');

for ($i = 0; $i < count($subscriptions); $i++) {
    if ($subscriptions[$i]['duration'] > 0) {
        $subscriptions[$i]['durationName'] = $frequenciesOptions[$subscriptions[$i]['duration']];
    }
}

$form->addHidden('type', $type);
$form->addHidden('id', $id);
$button = $form->addButtonSave(get_lang('Save'));

if (empty($currency)) {
    $button->setAttribute('disabled');
}

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $id = $formValues['id'];
    $type = $formValues['type'];
    $taxPerc = $formValues['tax_perc'] != '' ? (int) $formValues['tax_perc'] : null;

    $result = $plugin->updateSubscriptions($type, $id, $taxPerc);

    if ($result) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/subscriptions_courses.php');
    } else {
        header('Location:'.api_get_self().'?'.$queryString);
    }

    exit;
}

$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('SubscriptionAdd');
$interbreadcrumb[] = [
    'url' => 'subscriptions_courses.php',
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => 'subscriptions_courses.php',
    'name' => $plugin->get_lang('SubscriptionList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('items_form', $form->returnForm());
$template->assign('frequency_form', $frequencyForm->returnForm());
$template->assign('subscriptions', $subscriptions);
$template->assign('currencyIso', $currencyIso);

$content = $template->fetch('buycourses/view/configure_subscription.tpl');
$template->assign('content', $content);

$template->display_one_col_template();
