<?php
/* For license terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

if ($plugin->get('enabled') !== 'true') {
    api_not_allowed(true);
}

$em = Database::getManager();

$criteria = Criteria::create()
    ->where(
        Criteria::expr()->isNull('parent')
    );

$tools = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool')->matching($criteria);

$interbreadcrumb[] = ['url' => api_get_path(WEB_CODE_PATH).'admin/index.php', 'name' => get_lang('PlatformAdmin')];

$htmlHeadXtra[] = api_get_css(
    api_get_path(WEB_PLUGIN_PATH).'ims_lti/assets/style.css'
);

$template = new Template($plugin->get_title());
$template->assign('tools', $tools);

$content = $template->fetch('ims_lti/view/admin.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
