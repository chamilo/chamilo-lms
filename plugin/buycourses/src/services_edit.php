<?php
/* For license terms, see /license.txt */

/**
 * Create new Services for the Buy Courses plugin
 * @package chamilo.plugin.buycourses
 */

$cidReset = true;

require_once '../../../main/inc/global.inc.php';

$serviceId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;

if (!$serviceId) {
    header('Location: configuration.php');
}

$plugin = BuyCoursesPlugin::create();
$currency = $plugin->getSelectedCurrency();
$em = Database::getManager();
$users = $em->getRepository('ChamiloUserBundle:User')->findAll();
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
    'url' => 'configuration.php',
    'name' => $plugin->get_lang('Configuration')
];

$service = $plugin->getServices($serviceId);

$formDefaultValues = [
    'name' => $service['name'],
    'description' => $service['description'],
    'price' => $service['price'],
    'duration_days' => $service['duration_days'],
    'owner_id' => intval($service['owner_id']),
    'applies_to' => intval($service['applies_to']),
    'visibility' => ($service['visibility'] == 1) ? true : false,
    'image' =>
    is_file(api_get_path(SYS_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$serviceId.'.png')
        ?
    api_get_path(WEB_PLUGIN_PATH).'buycourses/uploads/services/images/simg-'.$serviceId.'.png'
        :
    api_get_path(WEB_CODE_PATH).'img/session_default.png',
    'video_url' => $service['video_url'],
    'service_information' => $service['service_information']
];

$form = new FormValidator('Services');
$form->addText('name', $plugin->get_lang('ServiceName'));
$form->addTextarea('description', $plugin->get_lang('Description'));
$form->addElement(
    'number',
    'price',
    [$plugin->get_lang('Price'), null, $currency['iso_code']],
    ['step' => 0.01]
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
    ($formDefaultValues['image'] != '' ? get_lang('UpdateImage') : get_lang(
        'AddImage'
    )),
    array('id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '16 / 9')
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
    header('Location: configuration.php');
    exit;
}

$templateName = $plugin->get_lang('EditService');
$tpl = new Template($templateName);

$tpl->assign('header', $templateName);
$tpl->assign('content', $form->returnForm());
$tpl->display_one_col_template();
