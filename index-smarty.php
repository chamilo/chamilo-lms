<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main
 */

define('CHAMILO_HOMEPAGE', true);

$language_file = array('courses', 'index');

/* Flag forcing the 'current course' reset, as we're not inside a course anymore. */
// Maybe we should change this into an api function? an example: Coursemanager::unset();
$cidReset = true;

/* Included libraries */
// The section (for the tabs).
$this_section = SECTION_CAMPUS;

require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';

$header_title = null;
if (!api_is_anonymous()) {
	$header_title = " ";
}

$index = new IndexManager($header_title);
 
$tpl = $index->tpl->get_template('layout/layout_two_col.tpl');

$user_id        = api_get_user_id();

//@todo move this inside the IndexManager
$index->tpl->assign('login_block', 				$index->show_login_form($user_id));
$index->tpl->assign('teacher_block', 			$index->display_teacher_link($user_id));
$index->tpl->assign('home_page', 				$index->return_home_page());

$index->tpl->assign('profile_block', 			$index->return_profile_block());
$index->tpl->assign('notice_block',				$index->return_notice($home));
$index->tpl->assign('plugin_campushomepage', 	$index->return_plugin_campushomepage());

$index->tpl->display($tpl);