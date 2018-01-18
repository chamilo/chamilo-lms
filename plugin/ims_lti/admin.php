<?php
/* For license terms, see /license.txt */
$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = ImsLtiPlugin::create();

$em = Database::getManager();
$tools = $em->getRepository('ChamiloPluginBundle:ImsLti\ImsLtiTool')->findAll();

$template = new Template($plugin->get_title());
$template->assign('tools', $tools);

$content = $template->fetch('ims_lti/view/admin.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
