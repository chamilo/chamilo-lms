<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\LtiProvider\Entity\Platform;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/LtiProviderPlugin.php';

api_protect_admin_script();

$plugin = LtiProviderPlugin::create();

$pluginEntity = Container::getPluginRepository()->findOneByTitle('LtiProvider');
$currentAccessUrl = Container::getAccessUrlUtil()->getCurrent();
$pluginConfiguration = $pluginEntity?->getConfigurationsByAccessUrl($currentAccessUrl);

$isPluginEnabled = $pluginEntity
    && $pluginEntity->isInstalled()
    && $pluginConfiguration
    && $pluginConfiguration->isActive();

if (!$isPluginEnabled) {
    api_not_allowed(true);
}

$em = Database::getManager();

/** @var Platform[] $platforms */
$platforms = $em->getRepository(Platform::class)->findAll();

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'admin/index.php',
    'name' => get_lang('Administration'),
];

$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'LtiProvider/assets/style.css'
);

$template = new Template($plugin->get_title());
$template->assign('platforms', $platforms);

$content = $template->fetch('LtiProvider/view/provider_admin.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
