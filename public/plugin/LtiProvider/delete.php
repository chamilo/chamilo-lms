<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\LtiProvider\Entity\Platform;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
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

$em->remove($platform);
$em->flush();

Display::addFlash(
    Display::return_message($plugin->get_lang('PlatformDeleted'), 'success')
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'LtiProvider/admin.php');
exit;
