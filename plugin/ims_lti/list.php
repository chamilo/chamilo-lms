<?php
/* For license terms, see /license.txt */
$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();
$em = Database::getManager();

$tools = Database::select('*', ImsLtiPlugin::TABLE_TOOL);

$template = new Template($plugin->get_title());
$template->assign('tools', $tools);

$content = $template->fetch('ims_lti/view/list.tpl');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
