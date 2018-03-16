<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$course_info = api_get_course_info();

$directory = $course_info['directory'];
$title = $course_info['title'];

// Preparing a confirmation message.
$link = api_get_path(WEB_COURSE_PATH).$directory.'/';

$tpl = new Template(get_lang('ThingsToDo'));

$tpl->assign('course_url', $link);
$tpl->assign('course_title', Display::url($title, $link));
$tpl->assign('course_id', $course_info['code']);
$tpl->assign('just_created', isset($_GET['first']) && $_GET['first'] ? 1 : 0);
$add_course_tpl = $tpl->get_template('create_course/add_course.tpl');
$content = $tpl->fetch($add_course_tpl);

$tpl->assign('content', $content);
$template = $tpl->get_template('layout/layout_1_col.tpl');
$tpl->display($template);
