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

require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';

// The section (for the tabs).
$this_section = SECTION_CAMPUS;

$header_title = null;
if (!api_is_anonymous()) {
	$header_title = " ";
}

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('bxslider'));
$htmlHeadXtra[] ='
<script type="text/javascript">
$(document).ready(function(){
	$("#slider").bxSlider({
		infiniteLoop	: true,
		auto			: true,
		pager			: true,
		autoHover		: true,
		pause			: 10000
	});
});
</script>';


$index = new IndexManager($header_title);
if (api_get_user_id()) {
	$tpl = $index->tpl->get_template('layout/layout_3_col.tpl');
} else {
	$tpl = $index->tpl->get_template('layout/layout_2_col.tpl');
}


//@todo move this inside the IndexManager

$index->tpl->assign('announcements_block', 		$index->return_announcements());
$index->tpl->assign('teacher_block', 			$index->return_teacher_link());
$index->tpl->assign('home_page_block', 			$index->return_home_page());

$index->tpl->assign('profile_block', 			$index->return_profile_block());
$index->tpl->assign('notice_block',				$index->return_notice());
$index->tpl->assign('plugin_campushomepage', 	$index->return_plugin_campushomepage());

$index->tpl->display($tpl);