<?php
/* For license terms, see /license.txt */
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$em = Database::getManager();

$form = new FrmAdd('ism_lti_create_tool');
$form->build();

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
        )
        ->setPrivacy(
            isset($formValues['share_name']),
            isset($formValues['share_email']),
            isset($formValues['share_picture'])
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
