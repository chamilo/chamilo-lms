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

$plugin = BuyCoursesPlugin::create();

if (isset($_GET['action'], $_GET['d'], $_GET['n'])) {
    if ($_GET['action'] == 'delete_frequency') {
        if (is_numeric($_GET['d'])) {
            $frequency = $plugin->selectFrequency($_GET['d']);

            if (!empty($frequency)) {
                $subscriptionsItems = $plugin->getSubscriptionsItemsByDuration($_GET['d']);

                if (empty($subscriptionsItems)) {
                    $plugin->deleteFrequency($_GET['d']);

                    Display::addFlash(
                        Display::return_message($plugin->get_lang('FrequencyRemoved'), 'success')
                    );
                } else {
                    Display::addFlash(
                        Display::return_message($plugin->get_lang('SubscriptionPeriodOnUse'), 'error')
                    );
                }
            } else {
                Display::addFlash(
                    Display::return_message($plugin->get_lang('FrequencyNotExits'), 'error')
                );
            }
        } else {
            Display::addFlash(
                Display::return_message($plugin->get_lang('FrequencyIncorrect'), 'error')
            );
        }

        header('Location: '.api_get_self());
        exit;
    }
}

$frequencies = $plugin->getFrequenciesList();

$globalSettingsParams = $plugin->getGlobalParameters();

$form = new FormValidator('add_frequency');

$form->addText('name', get_lang('Name'), false);

$form->addElement(
    'number',
    'duration',
    [$plugin->get_lang('Duration'), $plugin->get_lang('Days')],
    ['step' => 1, 'placeholder' => $plugin->get_lang('SubscriptionFrequencyValueDays')]
);

$button = $form->addButtonSave(get_lang('Save'));

if ($form->validate()) {
    $formValues = $form->getSubmitValues();
    $duration = $formValues['duration'];
    $name = $formValues['name'];

    $frequency = $plugin->selectFrequency($duration);

    if (!empty($frequency)) {
        $result = $plugin->updateFrequency($duration, $name);

        if (!isset($result)) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('FrequencyNotUpdated'), 'error')
            );
        }
    } else {
        $result = $plugin->addFrequency($duration, $name);

        if (!isset($result)) {
            Display::addFlash(
                Display::return_message($plugin->get_lang('FrequencyNotSaved'), 'error')
            );
        }
    }

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'buycourses/src/configure_frequency.php');

    exit;
}

//$form->setDefaults($formDefaults);

$templateName = $plugin->get_lang('FrequencyAdd');
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
$template->assign('frequencies_list', $frequencies);

$content = $template->fetch('buycourses/view/configure_frequency.tpl');
$template->assign('content', $content);

$template->display_one_col_template();
