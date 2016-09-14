<?php
/* For license terms, see /license.txt */
$cidReset = true;

require '../../main/inc/global.inc.php';

api_protect_admin_script();

$toolId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if (empty($toolId)) {
    api_not_allowed(true);
}

$plugin = ImsLtiPlugin::create();
$tool = ImsLtiTool::fetch($toolId);

if (empty($tool)) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('NoTool'), 'error')
    );

    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'ims_lti/list.php');
    exit;
}

$form = new FormValidator('ims_lti_edit_tool');
$form->addText('name', get_lang('Name'));
$form->addTextarea('description', get_lang('Description'));
$form->addText('url', get_lang('Url'));
$form->addText('consumer_key', $plugin->get_lang('ConsumerKey'));
$form->addText('shared_secret', $plugin->get_lang('SharedSecret'));
$form->addTextarea('custom_params', $plugin->get_lang('CustomParams'));
$form->addButtonCreate($plugin->get_lang('AddExternalTool'));
$form->addHidden('id', $tool->getId());
$form->setDefaults([
    'name' => $tool->getName(),
    'description' => $tool->getDescription(),
    'url' => $tool->getLaunchUrl(),
    'consumer_key' => $tool->getConsumerKey(),
    'shared_secret' => $tool->getSharedSecret(),
    'custom_params' => $tool->getCustomParams()
]);

if ($form->validate()) {
    $formValues = $form->exportValues();

    $tool
        ->setName($formValues['name'])
        ->setDescription($formValues['description'])
        ->setLaunchUrl($formValues['url'])
        ->setConsumerKey($formValues['consumer_key'])
        ->setSharedSecret($formValues['shared_secret'])
        ->setCustomParams($formValues['custom_params'])
        ->save();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolEdited'), 'success')
    );

    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'ims_lti/list.php');
    exit;
}

$template = new Template($plugin->get_lang('EditExternalTool'));
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
