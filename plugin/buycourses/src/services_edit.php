<?php
/* For license terms, see /license.txt */

/**
 * Create new Services for the Buy Courses plugin.
 *
 * @package chamilo.plugin.buycourses
 */
$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$serviceId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;

if (!$serviceId) {
    header('Location: list.php');
    exit;
}

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();
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
// breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'buycourses/index.php',
    'name' => $plugin->get_lang('plugin_title'),
];
$interbreadcrumb[] = [
    'url' => 'list_service.php',
    'name' => $plugin->get_lang('Services'),
];

$globalSettingsParams = $plugin->getGlobalParameters();
$service = $plugin->getService($serviceId);

$formDefaultValues = [
    'name' => $service['name'],
    'description' => $service['description'],
    'price' => $service['price'],
    'tax_perc' => $service['tax_perc'],
    'duration_days' => $service['duration_days'],
    'owner_id' => intval($service['owner_id']),
    'applies_to' => intval($service['applies_to']),
    'visibility' => ($service['visibility'] == 1) ? true : false,
    'image' => is_file(api_get_path(SYS_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$serviceId.'.png')
            ? api_get_path(WEB_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$serviceId.'.png'
            : api_get_path(WEB_CODE_PATH).'img/session_default.png',
    'video_url' => $service['video_url'],
    'service_information' => $service['service_information'],
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
    $formDefaultValues['image'] != '' ? get_lang('UpdateImage') : get_lang('AddImage'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '16 / 9']
);
$form->addText('video_url', get_lang('VideoUrl'), false);
$form->addHtmlEditor('service_information', $plugin->get_lang('ServiceInformation'), false);
$form->addHidden('id', $serviceId);
$form->addButtonSave(get_lang('Edit'));
$form->addHtml('<br /><br /><br /><br />');
$form->addButtonDelete($plugin->get_lang('DeleteThisService'), 'delete_service');
$form->setDefaults($formDefaultValues);
if ($form->validate()) {
    $values = $form->getSubmitValues();

    if (isset($values['delete_service'])) {
        $plugin->deleteService($serviceId);
        Display::addFlash(
            Display::return_message($plugin->get_lang('ServiceDeleted'), 'error')
        );
    } else {
        $plugin->updateService($values, $serviceId);
        Display::addFlash(
            Display::return_message($plugin->get_lang('ServiceEdited'), 'success')
        );
    }
    header('Location: list_service.php');
    exit;
}

$templateName = $plugin->get_lang('EditService');
$tpl = new Template($templateName);

$tpl->assign('header', $templateName);
$tpl->assign('content', $form->returnForm());
$tpl->display_one_col_template();
