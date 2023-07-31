<?php
/* For license terms, see /license.txt */

/**
 * Create new Services for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();
$em = Database::getManager();
$users = UserManager::getRepository()->findAll();
$userOptions = [];
if (!empty($users)) {
    foreach ($users as $user) {
        $userOptions[$user->getId()] = $user->getCompleteNameWithUsername();
    }
}

api_protect_admin_script(true);

$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

//view
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];
$interbreadcrumb[] = [
    'url' => 'list_service.php',
    'name' => $plugin->get_lang('Services'),
];

$globalSettingsParams = $plugin->getGlobalParameters();

$formDefaultValues = [
    'price' => 0,
    'tax_perc' => $globalSettingsParams['global_tax_perc'],
    'duration_days' => 0,
    'applies_to' => 0,
    'visibility' => true,
];

$form = new FormValidator('Services');
$form->addText('name', $plugin->get_lang('ServiceName'));
$form->addHtmlEditor('description', $plugin->get_lang('Description'));
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currency['iso_code']],
    ['step' => 0.01]
);
$form->addElement(
    'number',
    'tax_perc',
    [$plugin->get_lang('TaxPerc'), $plugin->get_lang('TaxPercDescription'), '%'],
    ['step' => 1, 'placeholder' => $globalSettingsParams['global_tax_perc'].'% '.$plugin->get_lang('ByDefault')]
);
$form->addElement(
    'number',
    'duration_days',
    [$plugin->get_lang('Duration'), null, get_lang('Days')],
    ['step' => 1]
);
$form->addElement(
    'radio',
    'applies_to',
    $plugin->get_lang('AppliesTo'),
    get_lang('None'),
    0
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('User'),
    1
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Course'),
    2
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('Session'),
    3
);
$form->addElement(
    'radio',
    'applies_to',
    null,
    get_lang('TemplateTitleCertificate'),
    4
);
$form->addSelect(
    'owner_id',
    get_lang('Owner'),
    $userOptions
);
$form->addCheckBox('visibility', $plugin->get_lang('VisibleInCatalog'));
$form->addFile(
    'picture',
    (get_lang(
        'AddImage'
    )),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '16 / 9']
);
$form->addText('video_url', get_lang('VideoUrl'), false);
$form->addHtmlEditor('service_information', $plugin->get_lang('ServiceInformation'), false);
$form->addButtonSave(get_lang('Add'));
$form->setDefaults($formDefaultValues);

if ($form->validate()) {
    $values = $form->getSubmitValues();

    $plugin->storeService($values);

    Display::addFlash(
        Display::return_message($plugin->get_lang('ServiceAdded'), 'success')
    );

    header('Location: list_service.php');
    exit;
}

$templateName = $plugin->get_lang('NewService');
$tpl = new Template($templateName);

$tpl->assign('header', $templateName);
$tpl->assign('content', $form->returnForm());
$tpl->display_one_col_template();
