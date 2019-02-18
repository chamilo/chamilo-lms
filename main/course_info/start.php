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
$tpl->assign('course_title', $title);
$tpl->assign('course', $course_info);
$tpl->assign('just_created', isset($_GET['first']) && $_GET['first'] ? 1 : 0);
$add_course_tpl = $tpl->get_template('create_course/start.html.twig');
$content = $tpl->fetch($add_course_tpl);

$tpl->assign('content', $content);
$tpl->display_one_col_template();
