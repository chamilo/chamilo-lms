<?php // $Id: index.php 22073 2009-07-14 15:49:30Z darkvela $
 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	@package dokeos.main
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Refactoring
* 	@version $Id: index.php 22073 2009-07-14 15:49:30Z darkvela $
*   @todo check the different @todos in this page and really do them
* 	@todo check if the news management works as expected
*/


// only this script should have this constant defined. This is used to activate the javascript that
// gives the login name automatic focus in header.inc.html.
/** @todo  Couldn't this be done using the $HtmlHeadXtra array? */
define('DOKEOS_HOMEPAGE', true);

// the language file
$language_file = array ('courses', 'index');

/* Flag forcing the 'current course' reset, as we're not inside a course anymore  */
// maybe we should change this into an api function? an example: Coursemanager::unset();
$cidReset = true;


/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
/** @todo make all the library files consistent, use filename.lib.php and not filename.lib.inc.php */
require_once ('main/inc/global.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once 'main/chat/chat_functions.lib.php';
$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);
$setting_show_also_closed_courses = (api_get_setting('show_closed_courses')=='true') ? true : false;

// the section (for the tabs)
$this_section = SECTION_CAMPUS;
/*
-----------------------------------------------------------
	Action Handling
-----------------------------------------------------------
*/
/** @todo 	wouldn't it make more sense if this would be done in local.inc.php so that local.inc.php become the only place where authentication is done?
 * 			by doing this you could logout from any page instead of only from index.php. From the moment there is a logout=true in the url you will be logged out
 * 			this can be usefull when you are on an open course and you need to log in to edit something and you immediately want to check how anonymous users
 * 			will see it.
 */
 $my_user_id=api_get_user_id();
if (!empty($_GET['logout'])) {
	logout();
}
/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$main_category_table 	= Database :: get_main_table(TABLE_MAIN_CATEGORY);
$track_login_table 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

/*
-----------------------------------------------------------
	Constants and CONFIGURATION parameters
-----------------------------------------------------------
*/
/** @todo these configuration settings should move to the dokeos config settings */
/** defines wether or not anonymous visitors can see a list of the courses on the Dokeos homepage that are open to the world */
$_setting['display_courses_to_anonymous_users'] = 'true';

/** @todo remove this piece of code because this is not used */
if (isset($_user['user_id'])) {
	$nameTools = api_get_setting('siteName');
}


/*
==============================================================================
		LOGIN
==============================================================================
*/
/**
 * @todo This piece of code should probably move to local.inc.php where the actual login / logout procedure is handled.
 * @todo consider removing this piece of code because does nothing.
 */
if (isset($_GET['submitAuth']) && $_GET['submitAuth'] == 1) {
	// nice lie!!!
	echo 'Attempted breakin - sysadmins notified.';
	session_destroy();
	die();
}

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
 * @todo check if this code is used. I think this code is never executed because after clicking the submit button
 * 		 the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
 * 		 on api_get_setting('page_after_login')
 */
if (!empty($_POST["submitAuth"])) {
	// the user is already authenticated, we now find the last login of the user.
	if (isset ($_user['user_id'])) {
		$sql_last_login = "SELECT UNIX_TIMESTAMP(login_date)
								FROM $track_login_table
								WHERE login_user_id = '".$_user['user_id']."'
								ORDER BY login_date DESC LIMIT 1";
		$result_last_login = api_sql_query($sql_last_login, __FILE__, __LINE__);
		if (!$result_last_login)
			if (Database::num_rows($result_last_login) > 0) {
				$user_last_login_datetime = Database::fetch_array($result_last_login);
				$user_last_login_datetime = $user_last_login_datetime[0];
				api_session_register('user_last_login_datetime');
			}
		mysql_free_result($result_last_login);

		//event_login();
		if (api_is_platform_admin()) {
			// decode all open event informations and fill the track_c_* tables
			include (api_get_path(LIBRARY_PATH)."stats.lib.inc.php");
			decodeOpenInfos();
		}
	}
} // end login -- if($_POST["submitAuth"])
else {
	// only if login form was not sent because if the form is sent the user was already on the page.
	event_open();
}

// the header
Display :: display_header('', 'dokeos');

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
echo '<div class="maincontent" id="content">';

// Plugins for loginpage_main AND campushomepage_main
if (!api_get_user_id()) {
	api_plugin('loginpage_main');
} else {
	api_plugin('campushomepage_main');
}

$home= 'home/';
if ($_configuration['multiple_access_urls']==true) {
	$access_url_id = api_get_current_access_url_id();										 
	if ($access_url_id != -1){						
		$url_info = api_get_access_url($access_url_id);
		// "http://" and the final "/" replaced						
		$url = substr($url_info['url'],7,strlen($url_info['url'])-8);						
		$clean_url = replace_dangerous_char($url);
		$clean_url = str_replace('/','-',$clean_url);
		$clean_url = $clean_url.'/';
		$home_old  = 'home/'; 
		$home= 'home/'.$clean_url;
	}
}

// Including the page for the news
$page_included = false;

if (!empty ($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/',$_GET['include'])) {
	include ('./'.$home.$_GET['include']);
	$page_included = true;
} else {
	
	if (!empty($_SESSION['user_language_choice'])) {
		$user_selected_language=$_SESSION['user_language_choice'];
	} elseif(!empty($_SESSION['_user']['language'])) {
		$user_selected_language=$_SESSION['_user']['language'];
	} else {
		$user_selected_language=get_setting('platformLanguage');
	}
	
	if(!file_exists($home.'home_news_'.$user_selected_language.'.html')) {
		if (file_exists($home.'home_top.html'))
			$home_top_temp=file($home.'home_top.html');
		else {
			$home_top_temp=file($home_old.'home_top.html');
		} 
		$home_top_temp=implode('',$home_top_temp);
		$open=str_replace('{rel_path}',api_get_path(REL_PATH),$home_top_temp);
		echo $open;
	} else {
		if(file_exists($home.'home_top_'.$user_selected_language.'.html')) {
			$home_top_temp = file_get_contents($home.'home_top_'.$user_selected_language.'.html');
		} else {
			$home_top_temp = file_get_contents($home.'home_top.html');
		}
		$open=str_replace('{rel_path}',api_get_path(REL_PATH),$home_top_temp);
		echo $open;
	}
}

// Display System announcements
$announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
$announcement = intval($announcement);

if (isset($_user['user_id'])) {
	$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
	SystemAnnouncementManager :: display_announcements($visibility, $announcement);
} else {
	SystemAnnouncementManager :: display_announcements(VISIBLE_GUEST, $announcement);
}

// Display courses and category list
if (!$page_included) {
	if (api_get_setting('display_categories_on_homepage') == 'true') {
		echo '<div class="home_cats">';
		display_anonymous_course_list();
		echo '</div>';
	}
}
echo '</div>';

// display right menu: language form, login section + useful weblinks
echo '<div class="menu" id="menu">';
display_anonymous_right_menu();
echo '</div>';

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();

/**
 * This function handles the logout and is called whenever there is a $_GET['logout']
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function logout() 
{
	global $_configuration, $extAuthSource;
	// variable initialisation
	$query_string='';

	if (!empty($_SESSION['user_language_choice'])) {
		$query_string='?language='.$_SESSION['user_language_choice'];
	}

	// Database table definition
	$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

	// selecting the last login of the user
	$uid = intval($_GET['uid']);
	$sql_last_connection="SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='$uid' ORDER BY login_date DESC LIMIT 0,1";
	$q_last_connection=api_sql_query($sql_last_connection);
	if (Database::num_rows($q_last_connection)>0) {
		$i_id_last_connection=Database::result($q_last_connection,0,"login_id");
	}
	
	if (!isset($_SESSION['login_as'])) {
		$current_date=date('Y-m-d H:i:s',time());
		$s_sql_update_logout_date="UPDATE $tbl_track_login SET logout_date='".$current_date."' WHERE login_id='$i_id_last_connection'";
		api_sql_query($s_sql_update_logout_date);
	}
	LoginDelete($uid, $_configuration['statistics_database']); //from inc/lib/online.inc.php - removes the "online" status
	
	//the following code enables the use of an external logout function.
	//example: define a $extAuthSource['ldap']['logout']="file.php" in configuration.php
	// then a function called ldap_logout() inside that file 
	// (using *authent_name*_logout as the function name) and the following code 
	// will find and execute it 
	$uinfo = api_get_user_info($uid);
	if (($uinfo['auth_source'] != PLATFORM_AUTH_SOURCE) && is_array($extAuthSource)) {
		if (is_array($extAuthSource[$uinfo['auth_source']])) {
			$subarray = $extAuthSource[$uinfo['auth_source']];
			if (!empty($subarray['logout']) && file_exists($subarray['logout'])) {
				include_once($subarray['logout']);
				$logout_function = $uinfo['auth_source'].'_logout';
				if (function_exists($logout_function)) {
					$logout_function($uinfo);
				}
			}
		}
	}
	exit_of_chat($uid);
	api_session_destroy();
	header("Location: index.php$query_string");
	exit();
}

/**
 * This function checks if there are courses that are open to the world in the platform course categories (=faculties)
 *
 * @param unknown_type $category
 * @return boolean
 */
function category_has_open_courses($category) {
	global $setting_show_also_closed_courses;
	
	$user_identified = (api_get_user_id()>0 && !api_is_anonymous());
	$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
	$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
	while ($course = Database::fetch_array($sql_result)) {
		if ($setting_show_also_closed_courses == false) {
			if ((api_get_user_id()>0 
				and $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
				or ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
				return true; //at least one open course
			}
		} else  {
			if(isset($course['visibility'])){
				return true; //at least one course (does not matter weither it's open or not because $setting_show_also_closed_courses = true
			}			
		}
	}
	return false;
}

function display_create_course_link() {
	echo "<li><a href=\"main/create_course/add_course.php\">".get_lang("CourseCreate")."</a></li>";
}

function display_edit_course_list_links() {
	echo "<li><a href=\"main/auth/courses.php\">".get_lang("CourseManagement")."</a></li>";
}

/**
 * Displays the right-hand menu for anonymous users:
 * login form, useful links, help section
 * Warning: function defines globals
 * @version 1.0.1
 * @todo does $_plugins need to be global?
 */
function display_anonymous_right_menu() {
	global $loginFailed, $_plugins, $_user, $menu_navigation;

	$platformLanguage = api_get_setting('platformLanguage');

	if ( !($_user['user_id']) or api_is_anonymous($_user['user_id']) ) {  // only display if the user isn't logged in
		api_display_language_form(true);
		echo '<br />';
		display_login_form();

		if ($loginFailed) {
			echo '<br />';
			handle_login_failed();
		}
		if (api_get_setting('allow_lostpassword') == 'true' OR api_get_setting('allow_registration') == 'true') {
			echo '<div class="menusection"><span class="menusectioncaption">'.get_lang('MenuUser').'</span><ul class="menulist">';
			if (get_setting('allow_registration') <> 'false') {
				echo '<li><a href="main/auth/inscription.php">'.get_lang('Reg').'</a></li>';
			}
			if (get_setting('allow_lostpassword') == 'true') {
				display_lost_password_info();
			}
			echo '</ul></div>';
		}
		if (api_number_of_plugins('loginpage_menu') > 0) {
			echo '<div class="note" style="background: none">';
			api_plugin('loginpage_menu');
			echo '</div>';
		}
	}


	// My Account section
	if (isset($_SESSION['_user']['user_id']) && $_SESSION['_user']['user_id']!=0) {
		// tabs that are deactivated are added here


		$show_menu=false;
		$show_create_link=false;
		$show_course_link=false;	
		
		$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION["studentview"] != "studentenview");
		
		if ($display_add_course_link) {
			//display_create_course_link();
			$show_menu=true;
			$show_create_link=true;			
		}
				
		if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
				$show_menu=true;
				$show_course_link=true;		
		} else {	
			if (api_get_setting('allow_students_to_browse_courses')=='true') {			
				$show_menu=true;
				$show_course_link=true;				
			}	
		}
				
		if ($show_menu){
			echo "<div class=\"menusection\">";
			echo "<span class=\"menusectioncaption\">".get_lang("MenuUser")."</span>";
			echo "<ul class=\"menulist\">";
			if ($show_create_link)
				display_create_course_link();
			if ($show_course_link)
				display_edit_course_list_links();			
			echo "</ul>";
			echo "</div>";			
		}
		
		if (!empty($menu_navigation)) {
			echo "<div class=\"menusection\">";
			echo "<span class=\"menusectioncaption\">".get_lang("MainNavigation")."</span>";
			echo "<ul class=\"menulist\">";
			foreach($menu_navigation as $section => $navigation_info) {
				$current = ($section == $GLOBALS['this_section'] ? ' id="current"' : '');
				echo '<li'.$current.'>';
				echo '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
				echo '</li>';
				echo "\n";
			}
			echo "</ul>";
			echo '</div>';
		}
	}
	
	// help section
	/*** hide right menu "general" and other parts on anonymous right menu  *****/
	 
	$user_selected_language = api_get_interface_language();
	global $home, $home_old;
	if (!isset ($user_selected_language))
	{
		$user_selected_language = $platformLanguage;
	} 

	if (!file_exists($home.'home_menu_'.$user_selected_language.'.html') && file_exists($home.'home_menu.html') && file_get_contents($home.'home_menu.html')!='')
	{
		echo "<div class=\"menusection\">", "<span class=\"menusectioncaption\">".get_lang("MenuGeneral")."</span>";
	 	echo "<ul class=\"menulist\">";
		if (file_exists($home.'home_menu.html'))
			include ($home.'home_menu.html');
		else {
			include ($home_old.'home_menu.html');
		}
		echo '</ul>';
		echo '</div>';
	}

	elseif(file_exists($home.'home_menu_'.$user_selected_language.'.html') && file_get_contents($home.'home_menu_'.$user_selected_language.'.html')!='')
	{	
		echo "<div class=\"menusection\">", "<span class=\"menusectioncaption\">".get_lang("MenuGeneral")."</span>";
	 	echo "<ul class=\"menulist\">";
		include($home.'home_menu_'.$user_selected_language.'.html');
		echo '</ul>';
		echo '</div>';
	}	
	
	if ($_user['user_id'] && api_number_of_plugins('campushomepage_menu') > 0) {
		echo '<div class="note" style="background: none">';
		api_plugin('campushomepage_menu');
		echo '</div>';
	}
			
	// includes for any files to be displayed below anonymous right menu
	
	if (!file_exists($home.'home_notice_'.$user_selected_language.'.html') && file_exists($home.'home_notice.html') && file_get_contents($home.'home_notice.html')!='') {
		echo '<div class="note">';
		if (file_exists($home.'home_notice.html'))
			include ($home.'home_notice.html');
		else {
			include ($home_old.'home_notice.html');
		}
		echo '</div>';
	} elseif(file_exists($home.'home_notice_'.$user_selected_language.'.html') && file_get_contents($home.'home_notice_'.$user_selected_language.'.html')!='') {
		echo '<div class="note">';
		include($home.'home_notice_'.$user_selected_language.'.html'); 
		echo '</div>';
	}	
}

/**
*	Reacts on a failed login:
*	displays an explanation with
*	a link to the registration form.
*
*	@version 1.0.1
*/
function handle_login_failed() {
	if (!isset($_GET['error'])) {
		$message = get_lang("InvalidId");
		if (api_is_self_registration_allowed()) {
			$message = get_lang("InvalidForSelfRegistration");
		}
	} else {
		switch ($_GET['error']) {
			case '':
				$message = get_lang('InvalidId');
				if (api_is_self_registration_allowed()) {
					$message = get_lang('InvalidForSelfRegistration');
				}
				break;
			case 'account_expired':
				$message=get_lang('AccountExpired');
				break;
			case 'account_inactive':
				$message=get_lang('AccountInactive');
				break;
			case 'user_password_incorrect':
				$message=get_lang('InvalidId');
				break;
			case 'access_url_inactive':
				$message=get_lang('AccountURLInactive');
				break;
		}
	}
	echo "<div id=\"login_fail\">".$message."</div>";
}

/**
*	Adds a form to let users login
*	@version 1.1
*/
function display_login_form() 
{
	$form = new FormValidator('formLogin');
	$form->addElement('text','login',get_lang('UserName'),array('size'=>17));
	$form->addElement('password','password',get_lang('Pass'),array('size'=>17));
	$form->addElement('style_submit_button','submitAuth',get_lang('langEnter'), array('class'=>'login', 'style'=>'float:left'));
	$renderer =& $form->defaultRenderer();
	$renderer->setElementTemplate('<div><label>{label}</label></div><div>{element}</div>');
	$form->display();
	if (api_get_setting('openid_authentication')=='true') {
		include_once('main/auth/openid/login.php');
		echo '<div>'.openid_form().'</div>';
	}
}
/**
 * Displays a link to the lost password section
 */
function display_lost_password_info() {
	echo "<li><a href=\"main/auth/lostPassword.php\">".get_lang("LostPassword")."</a></li>";
}

/**
* Display list of courses in a category.
* (for anonymous users)
*
* @version 1.1
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
*/
function display_anonymous_course_list() {
	$ctok = $_SESSION['sec_token'];
	$stok = Security::get_token();	
	
	//init
	$user_identified = (api_get_user_id()>0 && !api_is_anonymous());
	$web_course_path = api_get_path(WEB_COURSE_PATH);
	$category = Database::escape_string($_GET['category']);
	global $setting_show_also_closed_courses;

	// Database table definitions
	$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
	$main_category_table 	= Database :: get_main_table(TABLE_MAIN_CATEGORY);

	$platformLanguage = api_get_setting('platformLanguage');

	//get list of courses in category $category
	$sql_get_course_list = "SELECT * FROM $main_course_table cours
								WHERE category_code = '".Database::escape_string($_GET["category"])."'
								ORDER BY title, UPPER(visual_code)";
								
	//showing only the courses of the current access_url_id								
	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id !=-1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);			
			$sql_get_course_list="SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course 
					ON (url_rel_course.course_code=course.code)
					WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string($_GET["category"])."' ORDER BY title, UPPER(visual_code)";
		}
	}
	
	//removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
	$sql_result_courses = api_sql_query($sql_get_course_list, __FILE__, __LINE__);

	while ($course_result = Database::fetch_array($sql_result_courses)) {
		$course_list[] = $course_result;
	}

	$platform_visible_courses = '';
	// $setting_show_also_closed_courses
	if($user_identified) {
		if ($setting_show_also_closed_courses) {
			$platform_visible_courses = '';
		} else  {
			$platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' OR t3.visibility='".COURSE_VISIBILITY_OPEN_PLATFORM."' )";	
		}
	} else {
		if ($setting_show_also_closed_courses) {
			$platform_visible_courses = '';
		} else  {
			$platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' )";	
		}				
	}
	$sqlGetSubCatList = "
				SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
				FROM $main_category_table t1
				LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
				LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
				WHERE t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."
				GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
				
					
	//showing only the category of courses of the current access_url_id								
	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id !=-1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);	
			$sqlGetSubCatList = "
				SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
				FROM $main_category_table t1
				LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
				LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
				INNER JOIN $tbl_url_rel_course as url_rel_course 
					ON (url_rel_course.course_code=t3.code)
				WHERE access_url_id = $url_access_id AND t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."				
				GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
		}
	}
	
	$resCats = api_sql_query($sqlGetSubCatList, __FILE__, __LINE__);
	$thereIsSubCat = false;
	if (Database::num_rows($resCats) > 0) {
		$htmlListCat = "<h4 style=\"margin-top: 0px;\">".get_lang("CatList")."</h4>"."<ul>";
		while ($catLine = Database::fetch_array($resCats)) {
			if ($catLine['code'] != $category) {

				$category_has_open_courses = category_has_open_courses($catLine['code']);
				if ($category_has_open_courses) {
					//the category contains courses accessible to anonymous visitors
					$htmlListCat .= "<li>";
					$htmlListCat .= "<a href=\"".api_get_self()."?category=".$catLine['code']."\">".$catLine['name']."</a>";
					if (api_get_setting('show_number_of_courses') == 'true') {
						$htmlListCat .= " (".$catLine['nbCourse']." ".get_lang("Courses").")";
					}
					$htmlListCat .= "</li>\n";
					$thereIsSubCat = true;
				} elseif ($catLine['children_count'] > 0) {
					//the category has children, subcategories
					$htmlListCat .= "<li>";
					$htmlListCat .= "<a href=\"".api_get_self()."?category=".$catLine['code']."\">".$catLine['name']."</a>";
					$htmlListCat .= "</li>\n";
					$thereIsSubCat = true;
				}
				/************************************************************************
				 end changed code to eliminate the (0 courses) after empty categories
				 ************************************************************************/
				elseif (api_get_setting('show_empty_course_categories') == 'true') {
					$htmlListCat .= "<li>";
					$htmlListCat .= $catLine['name'];
					$htmlListCat .= "</li>\n";
					$thereIsSubCat = true;
				}//else don't set thereIsSubCat to true to avoid printing things if not requested
			} else {
				$htmlTitre = "<p>";
				if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
					$htmlTitre .= "<a href=\"".api_get_self()."\">"."&lt;&lt; ".get_lang("BackToHomePage")."</a>";
				}
				if (!is_null($catLine['parent_id']) || (api_get_setting('show_back_link_on_top_of_tree') <> 'true' && !is_null($catLine['code']))) {
					$htmlTitre .= "<a href=\"".api_get_self()."?category=".$catLine['parent_id']."\">"."&lt;&lt; ".get_lang("Up")."</a>";
				}
				$htmlTitre .= "</p>\n";
				if ($category != "" && !is_null($catLine['code'])) {
					$htmlTitre .= "<h3>".$catLine['name']."</h3>\n";
				} else {
					$htmlTitre .= "<h3>".get_lang("Categories")."</h3>\n";
				}
			}
		}
		$htmlListCat .= "</ul>\n";
	}
	echo $htmlTitre;
	if ($thereIsSubCat) {
		echo $htmlListCat;
	}
	while ($categoryName = Database::fetch_array($resCats)) {
		echo "<h3>", $categoryName['name'], "</h3>\n";
	}
	$numrows = Database::num_rows($sql_result_courses);
	$courses_list_string = '';
	$courses_shown = 0;
	if ($numrows > 0) {
		if ($thereIsSubCat) {
			$courses_list_string .= "<hr size=\"1\" noshade=\"noshade\">\n";
		}
		$courses_list_string .= "<h4 style=\"margin-top: 0px;\">".get_lang("CourseList")."</h4>\n"."<ul>\n";
		
		if (api_get_user_id()) {
			$courses_of_user = get_courses_of_user(api_get_user_id());
		}
		
		foreach ($course_list AS $course) {
			// $setting_show_also_closed_courses
			
			if ($setting_show_also_closed_courses==false) {
				// if we do not show the closed courses 
				// we only show the courses that are open to the world (to everybody)
				// and the courses that are open to the platform (if the current user is a registered user
				if( ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) OR ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
					$courses_shown++;
					$courses_list_string .= "<li>\n";
				$courses_list_string .= "<a href=\"".$web_course_path.$course['directory']."/\">".$course['title']."</a><br />";
				if (get_setting("display_coursecode_in_courselist") == "true") {
					$courses_list_string .= $course['visual_code'];
				}
				if (get_setting("display_coursecode_in_courselist") == "true" AND get_setting("display_teacher_in_courselist") == "true") {
					$courses_list_string .= " - ";
				}
				if (get_setting("display_teacher_in_courselist") == "true") {
					$courses_list_string .= $course['tutor_name'];
				}				
					if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] <> api_get_setting('platformLanguage')) {
						$courses_list_string .= ' - '.$course['course_language'];
					}
					$courses_list_string .= "</li>\n";
				}
			}
			// we DO show the closed courses.
			// the course is accessible if (link to the course homepage)
			// 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)
			// 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
			// 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED
			// 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting)
			// 5. the user is the platform admin api_is_platform_admin()
			// 
			else {
				$courses_shown++;
				$courses_list_string .= "<li>\n";
					if ( $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
							OR ($user_identified AND $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) 
							OR ($user_identified AND key_exists($course['code'],$courses_of_user) AND $course['visibility'] <> COURSE_VISIBILITY_CLOSED) 
							OR $courses_of_user[$course['code']]['status'] == '1'
							OR api_is_platform_admin()) {
						$courses_list_string .= "<a href=\"".$web_course_path.$course['directory']."/\">";
					}
					$courses_list_string .= $course['title'];
					if ( $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
							OR ($user_identified AND $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) 
							OR ($user_identified AND key_exists($course['code'],$courses_of_user) AND $course['visibility'] <> COURSE_VISIBILITY_CLOSED) 
							OR $courses_of_user[$course['code']]['status'] == '1'
							OR api_is_platform_admin()) {
						$courses_list_string .="</a><br />";
					}
					if (get_setting("display_coursecode_in_courselist") == "true") {
						$courses_list_string .= $course['visual_code'];
					}
					if (get_setting("display_coursecode_in_courselist") == "true" AND get_setting("display_teacher_in_courselist") == "true") {
						$courses_list_string .= " - ";
					}
					if (get_setting("display_teacher_in_courselist") == "true")
					{
						$courses_list_string .= $course['tutor_name'];
					}				
				if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] <> api_get_setting('platformLanguage')) {
					$courses_list_string .= ' - '.$course['course_language'];
				}
					if (api_get_setting('show_different_course_language') == 'true' && $course['course_language'] <> api_get_setting('platformLanguage')) {
						$courses_list_string .= ' - '.$course['course_language'];
					}
					// We display a subscription link if 
					// 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
					// 2
					if ($user_identified AND !key_exists($course['code'],$courses_of_user)) {
						if ($course['subscribe'] == '1') {
							$courses_list_string .= "<form action=\"main/auth/courses.php?action=subscribe&category=".$_GET['category']."\" method=\"post\">";
							$courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
							$courses_list_string .= "<input type=\"hidden\" name=\"subscribe\" value=\"".$course['code']."\" />";
							$courses_list_string .= "<input type=\"image\" name=\"unsub\" src=\"main/img/enroll.gif\" alt=\"".get_lang("Subscribe")."\" />".get_lang("Subscribe")."</form>";
						} else {
							$courses_list_string .= '<br />'.get_lang("SubscribingNotAllowed");
						}
					}
				$courses_list_string .= "</li>\n";
			}
		}
		$courses_list_string .= "</ul>\n";
	} else {
		// echo "<blockquote>",get_lang('_No_course_publicly_available'),"</blockquote>\n";
	}
	if ($courses_shown > 0) { //only display the list of courses and categories if there was more than
	  // 0 courses visible to the world (we're in the anonymous list here)
		echo $courses_list_string;
	}
	if ($category != "") {
		echo "<p>", "<a href=\"".api_get_self()."\"><b></b> ", Display :: return_icon('back.png', get_lang('BackToHomePage')),get_lang("BackToHomePage"), "</a>", "</p>\n";
	}
}

/**
 * retrieves all the courses that the user has already subscribed to
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @param int $user_id: the id of the user
 * @return array an array containing all the information of the courses of the given user
*/
function get_courses_of_user($user_id) {
	$table_course		= Database::get_main_table(TABLE_MAIN_COURSE);
	$table_course_user	= Database::get_main_table(TABLE_MAIN_COURSE_USER);

	// Secondly we select the courses that are in a category (user_course_cat<>0) and sort these according to the sort of the category
	$user_id = intval($user_id);
	$sql_select_courses="SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
								course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
								course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
		                        FROM    $table_course       course,
										$table_course_user  course_rel_user
		                        WHERE course.code = course_rel_user.course_code
		                        AND   course_rel_user.user_id = '".$user_id."'
		                        ORDER BY course_rel_user.sort ASC";
	$result = api_sql_query($sql_select_courses,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result)) {
		// we only need the database name of the course
		$courses[$row['k']] = array("db"=> $row['db'], "code" => $row['k'], "visual_code" => $row['vc'], "title" => $row['i'], "directory" => $row['dir'], "status" => $row['status'], "tutor" => $row['t'], "subscribe" => $row['subscr'], "unsubscribe" => $row['unsubscr'], "sort" => $row['sort'], "user_course_category" => $row['user_course_cat']);
	}
	return $courses;
}
