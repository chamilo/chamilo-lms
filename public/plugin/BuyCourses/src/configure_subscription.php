<?php

declare(strict_types=1);
/* For license terms, see /license.txt */

/*
 * Configuration page for subscriptions for the Buy Courses plugin.
 */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_admin_script(true);

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : 0;

if ($id <= 0 || $type <= 0) {
    api_not_allowed();
}

$queryString = 'id='.$id.'&type='.$type;

$editingCourse = BuyCoursesPlugin::PRODUCT_TYPE_COURSE === $type;
$editingSession = BuyCoursesPlugin::PRODUCT_TYPE_SESSION === $type;

$plugin = BuyCoursesPlugin::create();
$includeSession = 'true' === $plugin->get('include_sessions');
$entityManager = Database::getManager();
$currency = $plugin->getSelectedCurrency();

$subscriptionsListUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscriptions_courses.php';
if ($editingSession) {
    $subscriptionsListUrl = api_get_path(WEB_PLUGIN_PATH).'BuyCourses/src/subscriptions_sessions.php';
}

$backUrl = $subscriptionsListUrl;
$deleteActionUrl = api_get_self().'?'.$queryString;

if (empty($currency)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('CurrencyIsNotConfigured'), 'error')
    );
}

$productLabelText = '';
$productNameText = '';
$currencyIso = null;

if ($editingCourse) {
    $course = $entityManager->find(Course::class, $id);

    if (!$course) {
        api_not_allowed(true);
    }

    $courseItem = $plugin->getCourseForConfiguration($course, $currency);
    $currencyIso = $courseItem['currency'];

    $productLabelText = get_lang('Course');
    $productNameText = (string) $courseItem['course_title'];
} elseif ($editingSession) {
    if (!$includeSession) {
        api_not_allowed(true);
    }

    $session = $entityManager->find(Session::class, $id);

    if (!$session) {
        api_not_allowed(true);
    }

    $sessionItem = $plugin->getSessionForConfiguration($session, $currency);
    $currencyIso = $sessionItem['currency'];

    $productLabelText = get_lang('Session');
    $productNameText = (string) $sessionItem['session_name'];
} else {
    api_not_allowed(true);
}

$subscriptions = $plugin->getSubscriptions($type, $id);
if (!is_array($subscriptions)) {
    $subscriptions = [];
}

$taxPerc = 0;
if (!empty($subscriptions) && isset($subscriptions[0]['tax_perc'])) {
    $taxPerc = (int) $subscriptions[0]['tax_perc'];
}

$deleteAction = (string) ($_POST['action'] ?? '');
$deleteDuration = isset($_POST['duration']) ? (int) $_POST['duration'] : 0;

if ('delete_frequency' === $deleteAction) {
    if ($deleteDuration > 0) {
        $deleted = $plugin->deleteSubscription($type, $id, $deleteDuration);

        if ($deleted) {
            Display::addFlash(
                Display::return_message(get_lang('ItemRemoved'), 'success')
            );
        } else {
            Display::addFlash(
                Display::return_message($plugin->get_lang('SubscriptionNotDeleted'), 'error')
            );
        }
    } else {
        Display::addFlash(
            Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error')
        );
    }

    header('Location: '.api_get_self().'?'.$queryString);
    exit;
}

$globalSettingsParams = $plugin->getGlobalParameters();
$defaultGlobalTax = (int) ($globalSettingsParams['global_tax_perc'] ?? 0);

$form = new FormValidator('subscription_settings');
$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    [
        'step' => 1,
        'placeholder' => $defaultGlobalTax.'% '.$plugin->get_lang('ByDefault'),
    ]
);
$form->addHidden('type', (string) $type);
$form->addHidden('id', (string) $id);
$saveButton = $form->addButtonSave(get_lang('Save'));

if (empty($currency)) {
    $saveButton->setAttribute('disabled');
}

$form->setDefaults([
    'type' => $type,
    'id' => $id,
    'tax_perc' => $taxPerc,
]);

$frequenciesOptions = $plugin->getFrequencies();
if (!is_array($frequenciesOptions)) {
    $frequenciesOptions = [];
}

$frequencyForm = new FormValidator('frequency_config', 'post', api_get_self().'?'.$queryString);
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
    ['step' => 0.01, 'min' => 0],
    ['cols-size' => [3, 8, 1]]
);
$frequencyForm->addHidden('type', (string) $type);
$frequencyForm->addHidden('id', (string) $id);
$frequencyForm->addHidden('tax_perc', (string) $taxPerc);
$frequencyForm->addHidden('currency_id', (string) ($currency['id'] ?? 0));
$frequencyForm->addButtonCreate(get_lang('Add'));

$frequencyForm->setDefaults([
    'id' => $id,
    'type' => $type,
    'tax_perc' => $taxPerc,
    'currency_id' => $currency['id'] ?? 0,
]);

if ($frequencyForm->validate()) {
    $frequencyFormValues = $frequencyForm->getSubmitValues();

    $subscription = [
        'product_id' => (int) ($frequencyFormValues['id'] ?? 0),
        'product_type' => (int) ($frequencyFormValues['type'] ?? 0),
        'tax_perc' => '' !== (string) ($frequencyFormValues['tax_perc'] ?? '')
            ? (int) $frequencyFormValues['tax_perc']
            : null,
        'currency_id' => (int) ($currency['id'] ?? 0),
    ];

    $duration = isset($frequencyFormValues['duration']) ? (int) $frequencyFormValues['duration'] : 0;
    $price = isset($frequencyFormValues['price']) ? (float) $frequencyFormValues['price'] : 0.0;

    if ($duration <= 0 || $price <= 0) {
        Display::addFlash(
            Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error')
        );

        header('Location: '.api_get_self().'?'.$queryString);
        exit;
    }

    foreach ($subscriptions as $existingSubscription) {
        if ((int) ($existingSubscription['duration'] ?? 0) === $duration) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('SubscriptionAlreadyExists'), 'error')
            );

            header('Location: '.api_get_self().'?'.$queryString);
            exit;
        }
    }

    $subscription['frequencies'] = [
        [
            'duration' => $duration,
            'price' => $price,
        ],
    ];

    $result = $plugin->addNewSubscription($subscription);

    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('Saved'), 'success')
        );
    } else {
        Display::addFlash(
            Display::return_message($plugin->get_lang('SubscriptionErrorInsert'), 'error')
        );
    }

    header('Location: '.api_get_self().'?'.$queryString);
    exit;
}

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    $savedId = isset($formValues['id']) ? (int) $formValues['id'] : 0;
    $savedType = isset($formValues['type']) ? (int) $formValues['type'] : 0;
    $savedTaxPerc = '' !== (string) ($formValues['tax_perc'] ?? '')
        ? (int) $formValues['tax_perc']
        : null;

    $result = $plugin->updateSubscription($savedType, $savedId, $savedTaxPerc);

    if ($result) {
        Display::addFlash(
            Display::return_message(get_lang('Saved'), 'success')
        );

        header('Location: '.$subscriptionsListUrl);
    } else {
        Display::addFlash(
            Display::return_message($plugin->get_lang('SubscriptionNotUpdated'), 'error')
        );

        header('Location: '.api_get_self().'?'.$queryString);
    }

    exit;
}

foreach ($subscriptions as $index => $subscriptionRow) {
    $durationValue = (int) ($subscriptionRow['duration'] ?? 0);
    $subscriptions[$index]['durationName'] = $frequenciesOptions[$durationValue] ?? (string) $durationValue;
}

$templateName = $plugin->get_lang('SubscriptionAdd');
$interbreadcrumb[] = [
    'url' => $subscriptionsListUrl,
    'name' => get_lang('Configuration'),
];
$interbreadcrumb[] = [
    'url' => $subscriptionsListUrl,
    'name' => $plugin->get_lang('SubscriptionList'),
];

$template = new Template($templateName);
$template->assign('header', $templateName);
$template->assign('page_title', $templateName);
$template->assign('back_url', $backUrl);
$template->assign('product_label', $productLabelText);
$template->assign('product_name', $productNameText);
$template->assign('currencyIso', $currencyIso);
$template->assign('items_form', $form->returnForm());
$template->assign('frequency_form', $frequencyForm->returnForm());
$template->assign('subscriptions', $subscriptions);
$template->assign('subscriptions_count', count($subscriptions));
$template->assign('delete_action_url', $deleteActionUrl);

$content = $template->fetch('BuyCourses/view/configure_subscription.tpl');
$template->assign('content', $content);

$template->display_one_col_template();
