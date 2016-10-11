<?php
/* For license terms, see /license.txt */
$cidReset = true;

require '../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$form = new FormValidator('ism_lti_create_tool');
$form->addText('name', get_lang('Name'));
$form->addText('base_url', get_lang('BaseUrl'));
$form->addText('consumer_key', $plugin->get_lang('ConsumerKey'));
$form->addText('shared_secret', $plugin->get_lang('SharedSecret'));
$form->addTextarea('custom_params', $plugin->get_lang('CustomParams'));
$form->addButtonCreate($plugin->get_lang('AddExternalTool'));

if ($form->validate()) {
    $formValues = $form->exportValues();

    $externalTool = new ImsLtiTool();
    $externalTool
        ->setName($formValues['name'])
        ->setDescription($formValues['description'])
        ->setLaunchUrl($formValues['base_url'])
        ->setConsumerKey($formValues['consumer_key'])
        ->setSharedSecret($formValues['shared_secret'])
        ->setCustomParams($formValues['custom_params'])
        ->save();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolAdded'), 'success')
    );

    header('Location: ' . api_get_path(WEB_PLUGIN_PATH) . 'ims_lti/list.php');
    exit;
}

$template = new Template($plugin->get_lang('AddExternalTool'));
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
