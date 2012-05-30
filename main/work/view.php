<?php

$language_file = array('exercice', 'work', 'document', 'admin');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

require_once 'work.lib.php';

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : null;
$work = get_work_data_by_id($id);

if (empty($id) || empty($work)) {
	api_not_allowed();
}
$interbreadcrumb[] = array ('url' => 'work.php', 'name' => get_lang('StudentPublications'));

$my_folder_data = get_work_data_by_id($work['parent_id']);
$course_info = api_get_course_info();

if (user_is_author($id) || $course_info['show_score'] == 0 && $work['active'] == 1 && $work['accepted'] == 1) {
    $url_dir = 'work.php?&id=' . $my_folder_data['id'];
    $interbreadcrumb[] = array ('url' => $url_dir,'name' =>  $my_folder_data['title']);	
    $interbreadcrumb[] = array ('url' => '#','name' =>  $work['title']);	

    if (($course_info['show_score'] == 0 && $work['active'] == 1 && $work['accepted'] == 1) || api_is_allowed_to_edit() || ($work['user_id'] == api_get_user_id() && $work['active'] == 1 && $work['accepted'] == 1)) {
        $tpl = new Template();		
        $tpl->assign('work', $work);
        $template = $tpl->get_template('work/view.tpl');	
        $content  = $tpl->fetch($template);
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();	
    } else {
        api_not_allowed();	
    }
}