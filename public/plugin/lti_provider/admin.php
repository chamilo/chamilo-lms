<?php
/* For license terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';
require_once __DIR__.'/LtiProviderPlugin.php';

api_protect_admin_script();

$plugin = LtiProviderPlugin::create();

if ($plugin->get('enabled') !== 'true') {
    api_not_allowed(true);
}

$em = Database::getManager();

$platforms = $em->getRepository('ChamiloPluginBundle:LtiProvider\Platform')->findAll();

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];

$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'lti_provider/assets/style.css'
);

$template = new Template($plugin->get_title());
$template->assign('platforms', $platforms);

$content = $template->fetch('lti_provider/view/provider_admin.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
