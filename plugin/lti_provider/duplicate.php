<?php
/* For license terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
use Chamilo\PluginBundle\Entity\LtiProvider\Platform;

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

if (!$platform) {
    api_not_allowed(true);
}

$newPlatform = new Platform();
$newPlatform->setIssuer($platform->getIssuer());
$newPlatform->setClientId($platform->getClientId());
$newPlatform->setAuthLoginUrl($platform->getAuthLoginUrl());
$newPlatform->setAuthTokenUrl($platform->getAuthTokenUrl());
$newPlatform->setKeySetUrl($platform->getKeySetUrl());
$newPlatform->setDeploymentId($platform->getDeploymentId());
$newPlatform->setKid($platform->getKid());
$newPlatform->setToolProvider($platform->getToolProvider());

$em->persist($newPlatform);
$em->flush();

Display::addFlash(
    Display::return_message($plugin->get_lang('PlatformDuplicated'), 'success')
);

header('Location: '.api_get_path(WEB_PLUGIN_PATH).'lti_provider/edit.php?id='.$newPlatform->getId());
exit;
