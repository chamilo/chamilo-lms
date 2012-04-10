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
require_once 'main/chat/chat_functions.lib.php';

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

//set cookie for check if client browser are cookies enabled
setcookie("TestCookie", "cookies_yes", time()+3600);

$controller = new IndexManager($header_title);

//Actions
$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);
$setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

if (!empty($_GET['logout'])) {
	$controller->logout();
}

/* Table definitions */

/* Constants and CONFIGURATION parameters */
/** @todo these configuration settings should move to the Chamilo config settings. */

/** Defines wether or not anonymous visitors can see a list of the courses on the Chamilo homepage that are open to the world. */
$_setting['display_courses_to_anonymous_users'] = 'true';

/* LOGIN */

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login / logout procedure is handled.
 * @todo Consider removing this piece of code because does nothing.
 */
if (isset($_GET['submitAuth']) && $_GET['submitAuth'] == 1) {
	// nice lie!!!
	echo 'Attempted breakin - sysadmins notified.';
	session_destroy();
	die();
}

// Delete session neccesary for legal terms
if (api_get_setting('allow_terms_conditions') == 'true') {
	unset($_SESSION['update_term_and_condition']);
	unset($_SESSION['info_current_user']);
}
//If we are not logged in and customapages activated
if (!api_get_user_id() && api_get_setting('use_custom_pages') == 'true' ){
  require_once api_get_path(LIBRARY_PATH).'custompages.lib.php';
  CustomPages::displayPage('index-unlogged');
}

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
 * @todo Check if this code is used. I think this code is never executed because after clicking the submit button
 *       the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
 *       on api_get_setting('page_after_login').
 */

if (!empty($_POST['submitAuth'])) {
	// The user has been already authenticated, we are now to find the last login of the user.
	if (isset ($_user['user_id'])) {
		$track_login_table      = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
		$sql_last_login = "SELECT UNIX_TIMESTAMP(login_date)
                                FROM $track_login_table
                                WHERE login_user_id = '".$_user['user_id']."'
                                ORDER BY login_date DESC LIMIT 1";
		$result_last_login = Database::query($sql_last_login);
		if (!$result_last_login) {
			if (Database::num_rows($result_last_login) > 0) {
				$user_last_login_datetime = Database::fetch_array($result_last_login);
				$user_last_login_datetime = $user_last_login_datetime[0];
				api_session_register('user_last_login_datetime');
			}
		}
		Database::free_result($result_last_login);

		//event_login();
		if (api_is_platform_admin()) {
			// decode all open event informations and fill the track_c_* tables
			include api_get_path(LIBRARY_PATH).'stats.lib.inc.php';
			decodeOpenInfos();
		}
	}
	// End login -- if ($_POST['submitAuth'])
} else {
	// Only if login form was not sent because if the form is sent the user was already on the page.
	event_open();
}

if (api_get_setting('display_categories_on_homepage') == 'true') {
	$controller->tpl->assign('content', $controller->display_anonymous_course_list());
}

$controller->set_login_form();

//@todo move this inside the IndexManager
if (!api_is_anonymous()) {
	$controller->tpl->assign('profile_block', $controller->return_profile_block());
	
	if (api_is_platform_admin()) {
		$controller->tpl->assign('account_block',			$controller->return_account_block());
	} else {		
		$controller->tpl->assign('teacher_block', 			$controller->return_teacher_link());
	}
}

$controller->tpl->assign('hot_courses',             $controller->return_hot_courses());
$controller->tpl->assign('announcements_block', 	$controller->return_announcements());
$controller->tpl->assign('home_page_block', 		$controller->return_home_page());
$controller->tpl->assign('notice_block',			$controller->return_notice());

if (api_is_platform_admin() || api_is_drh()) {
    $controller->tpl->assign('skills_block',            $controller->return_skills_links());
}

$controller->tpl->display_two_col_template();