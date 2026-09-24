<?php
/* For license terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
use Chamilo\PluginBundle\Entity\LtiProvider\Platform;
use Chamilo\PluginBundle\LtiProvider\Form\FrmAdd;

require_once __DIR__.'/LtiProviderPlugin.php';

api_protect_admin_script();

if (!isset($_REQUEST['id'])) {
    api_not_allowed(true);
}

$sourcePlatformId = (int) $_REQUEST['id'];

$plugin = LtiProviderPlugin::create();
$em = Database::getManager();

/** @var Platform $sourcePlatform */
$sourcePlatform = $em->find('ChamiloPluginBundle:LtiProvider\Platform', $sourcePlatformId);

if (!$sourcePlatform) {
    api_not_allowed(true);
}

// GET only pre-fills a normal "add platform" form from the source platform's
// data; nothing is persisted until the admin reviews and submits it (POST).
$form = new FrmAdd('lti_provider_duplicate_platform', [], $sourcePlatform);
$form->build();

if ($form->validate()) {
    $formValues = $form->exportValues();

    $newPlatform = new Platform();
    $newPlatform->setIssuer($formValues['issuer']);
    $newPlatform->setClientId($formValues['client_id']);
    $newPlatform->setAuthLoginUrl($formValues['auth_login_url']);
    $newPlatform->setAuthTokenUrl($formValues['auth_token_url']);
    $newPlatform->setKeySetUrl($formValues['key_set_url']);
    $newPlatform->setDeploymentId($formValues['deployment_id']);
    $newPlatform->setKid($formValues['kid']);
    $toolProvider = (isset($formValues['tool_provider']) ? $formValues['tool_provider'] : $_POST['tool_provider']);
    $newPlatform->setToolProvider($toolProvider);

    $em->persist($newPlatform);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('PlatformDuplicated'), 'success')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'lti_provider/admin.php');
    exit;
}

$form->setDefaultValues();

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => api_get_path(WEB_PLUGIN_PATH).'lti_provider/admin.php', 'name' => $plugin->get_title()];

$pageTitle = $plugin->get_lang('AddPlatform');

$template = new Template($pageTitle);
$template->assign('form', $form->returnForm());

$content = $template->fetch('lti_provider/view/add.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
