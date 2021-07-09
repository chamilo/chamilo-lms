<?php
/* For license terms, see /license.txt */

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

/** @var Platform $platform */
$platform = $em->find('ChamiloPluginBundle:LtiProvider\Platform', $platformId);

$em->remove($platform);
$em->flush();

Display::addFlash(
    Display::return_message($plugin->get_lang('PlatformDeleted'), 'success')
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'lti_provider/admin.php');
exit;
