<?php

$language_file = array('exercice', 'work', 'document', 'admin');

require_once '../inc/global.inc.php';
require_once 'work.lib.php';

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
$work = get_work_data_by_id($id);

if (empty($id) || empty($work)) {
	api_not_allowed();
}

$interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));

if (api_is_allowed_to_edit() || $work['user_id'] == api_get_user_id()) {
	$tpl = new Template();		
	$tpl->assign('work', $work);
	$template = $tpl->get_template('work/view.tpl');	
	$content  = $tpl->fetch($template);
	$tpl->assign('content', $content);
	$tpl->display_one_col_template();	
} else {
	api_not_allowed();	
}