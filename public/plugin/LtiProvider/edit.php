<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\LtiProvider\Entity\Platform;
use Chamilo\PluginBundle\LtiProvider\Form\FrmEdit;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/src/Form/FrmEdit.php';
require_once __DIR__.'/LtiProviderPlugin.php';

api_protect_admin_script();

if (!isset($_REQUEST['id'])) {
    api_not_allowed(true);
}

$platformId = (int) $_REQUEST['id'];

$plugin = LtiProviderPlugin::create();
$em = Database::getManager();

/** @var Platform|null $platform */
$platform = $em->find(Platform::class, $platformId);

if (!$platform) {
    Display::addFlash(
        Display::return_message($plugin->get_lang('NoPlatform'), 'error')
    );
    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'LtiProvider/admin.php');
    exit;
}

$form = new FrmEdit('lti_provider_edit_platform', [], $platform);
$form->build();

if ($form->validate()) {
    $formValues = $form->exportValues();
    $platform->setIssuer($formValues['issuer']);
    $platform->setClientId($formValues['client_id']);
    $platform->setAuthLoginUrl($formValues['auth_login_url']);
    $platform->setAuthTokenUrl($formValues['auth_token_url']);
    $platform->setKeySetUrl($formValues['key_set_url']);
    $platform->setDeploymentId($formValues['deployment_id']);
    $platform->setKid($formValues['kid']);
    $toolProvider = $formValues['tool_provider'] ?? ($_POST['tool_provider'] ?? null);
    $platform->setToolProvider($toolProvider);

    $em->persist($platform);
    $em->flush();

    Display::addFlash(
        Display::return_message($plugin->get_lang('PlatformEdited'), 'success')
    );

    header('Location: '.api_get_path(WEB_PLUGIN_PATH).'LtiProvider/admin.php');
    exit;
}

$form->setDefaultValues();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PLUGIN_PATH).'LtiProvider/admin.php',
    'name' => $plugin->get_title(),
];

$pageTitle = $plugin->get_lang('EditPlatform');
$adminUrl = api_get_path(WEB_PLUGIN_PATH).'LtiProvider/admin.php';

$template = new Template($pageTitle);
$template->assign('form', $form->returnForm());
$template->assign('back_url', $adminUrl);

$content = $template->fetch('LtiProvider/view/add.tpl');

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
