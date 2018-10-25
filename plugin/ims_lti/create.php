<?php
/* For license terms, see /license.txt */
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$em = Database::getManager();

$form = new FormValidator('ism_lti_create_tool');
$form->addHeader($plugin->get_lang('ToolSettings'));
$form->addText('name', get_lang('Name'));
$form->addText('base_url', $plugin->get_lang('LaunchUrl'));
$form->addText('consumer_key', $plugin->get_lang('ConsumerKey'));
$form->addText('shared_secret', $plugin->get_lang('SharedSecret'));
$form->addButtonAdvancedSettings('lti_adv');
$form->addHtml('<div id="lti_adv_options" style="display:none;">');
$form->addTextarea('description', get_lang('Description'), ['rows' => 3]);
$form->addTextarea('custom_params', [$plugin->get_lang('CustomParams'), $plugin->get_lang('CustomParamsHelp')]);
$form->addCheckBox('deep_linking', $plugin->get_lang('SupportDeepLinking'), get_lang('Yes'));
$form->addHtml('</div>');
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
        ->setIsGlobal(true)
        ->setActiveDeepLinking(
            isset($formValues['deep_linking'])
        );

    $em->persist($externalTool);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('ToolAdded'), 'success')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'ims_lti/admin.php');
    exit;
}

$pageTitle = $plugin->get_lang('AddExternalTool');

$template = new Template($pageTitle);
$template->assign('form', $form->returnForm());

$content = $template->fetch('ims_lti/view/add.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
