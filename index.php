<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.main
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Refactoring
* 	@version $Id: index.php 12565 2007-06-07 13:52:17Z elixir_julian $
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
/** @todo make all the library files consistent use filename.lib.php and not filename.lib.inc.php */
require_once ('./main/inc/global.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);

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
if ($_GET['logout'])
{
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
/** defines weither empty course categories should appear in the list with the course categories */
$_setting['show_empty_course_categories'] = 'true';
/** defines weither the number of open courses has to appear after the course category (faculty) */
/** remark: actually count are only for direct children */
$_setting['show_number_of_courses'] = 'false';
/** Show the back link on top of the tree */
$_setting['show_back_link_on_top_of_tree'] = 'false';
/** Show the course language if it is not the same as the platform lanugage */
$_setting['show_different_course_language'] = 'true';


/** @todo remove this piece of code because this is not used */
if (isset ($_user['user_id']))
{
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
if ($_GET["submitAuth"] == 1)
{
	// nice lie!!!
	echo "Attempted breakin - sysadmins notified.";
	session_destroy();
	die();
}

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
 * @todo check if this code is used. I think this code is never executed because after clicking the submit button
 * 		 the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
 * 		 on api_get_setting('page_after_login')
 */
if ($_POST["submitAuth"])
{
	// the user is already authenticated, we now find the last login of the user.
	if (isset ($_user['user_id']))
	{
		$sql_last_login = "SELECT UNIX_TIMESTAMP(login_date)
								FROM $track_login_table
								WHERE login_user_id = '".$_user['user_id']."'
								ORDER BY login_date DESC LIMIT 1";
		$result_last_login = api_sql_query($sql_last_login, __FILE__, __LINE__);
		if (!$result_last_login)
			if (mysql_num_rows($result_last_login) > 0)
			{
				$user_last_login_datetime = mysql_fetch_array($result_last_login);
				$user_last_login_datetime = $user_last_login_datetime[0];
				api_session_register('user_last_login_datetime');
			}
		mysql_free_result($result_last_login);

		//event_login();
		if (api_is_platform_admin())
		{
			// decode all open event informations and fill the track_c_* tables
			include (api_get_path(LIBRARY_PATH)."stats.lib.inc.php");
			decodeOpenInfos();
		}
	}
} // end login -- if($_POST["submitAuth"])
else
{
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
if (!api_get_user_id())
{
	api_plugin('loginpage_main');
}
else
{
	api_plugin('campushomepage_main');
}

// Including the page for the news
$page_included = false;
if (!empty ($_GET['include']) && !strstr($_GET['include'], '/') && !strstr($_GET['include'], '\\') && strstr($_GET['include'], '.html'))
{
	include ('./home/'.$_GET['include']);
	$page_included = true;
}
else
{
	
	if(!empty($_SESSION['user_language_choice'])){
		$user_selected_language=$_SESSION['user_language_choice'];
	}
	elseif(!empty($_SESSION['_user']['language'])){
		$user_selected_language=$_SESSION['_user']['language'];
	}
	else{
		$user_selected_language=get_setting('platformLanguage');
	}
	
	if(!file_exists('home/home_news_'.$user_selected_language.'.html'))
	{
		$home_top_temp=file('home/home_top.html');
		$home_top_temp=implode('',$home_top_temp);
		$open=str_replace('{rel_path}',api_get_path(REL_PATH),$home_top_temp);
		echo $open;
	}
	else
	{
		if(file_exists('home/home_top_'.$user_selected_language.'.html')){
			include('home/home_top_'.$user_selected_language.'.html');
		}
		else{
			include('home/home_top.html');
		}
	}
}

// Display System announcements
$announcement = $_GET['announcement'] ? $_GET['announcement'] : -1;
$announcement = intval($announcement);
SystemAnnouncementManager :: display_announcements(VISIBLE_GUEST, $announcement);

// Display courses and category list
if (!$page_included)
{

	if (api_get_setting('display_courses_to_anonymous_users') == 'true')
	{
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
	// variable initialisation
	$query_string='';

	if(!empty($_SESSION['user_language_choice']))
	{
		$query_string='?language='.$_SESSION['user_language_choice'];
	}

	// Database table definition
	$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

	// selecting the last login of the user
	$uid = intval($_GET['uid']);
	$sql_last_connection="SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='$uid' ORDER BY login_date DESC LIMIT 0,1";
	$q_last_connection=mysql_query($sql_last_connection);
	$i_id_last_connection=mysql_result($q_last_connection,0,"login_id");

	$s_sql_update_logout_date="UPDATE $tbl_track_login SET logout_date=NOW() WHERE login_id='$i_id_last_connection'";
	api_sql_query($s_sql_update_logout_date);

	LoginDelete($uid, $_configuration['statistics_database']);

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
function category_has_open_courses($category)
{
	$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
	$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
	while ($course = mysql_fetch_array($sql_result))
	{
		if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)
		{
			return true; //at least one open course
		}
	}
	return false;
}

function display_create_course_link()
{
	echo "<li><a href=\"main/create_course/add_course.php\">".get_lang("CourseCreate")."</a></li>";
}

function display_edit_course_list_links()
{
	echo "<li><a href=\"main/auth/courses.php\">".get_lang("CourseManagement")."</a></li>";
}

/**
 * Displays the right-hand menu for anonymous users:
 * login form, useful links, help section
 * Warning: function defines globals
 * @version 1.0.1
 * @todo does $_plugins need to be global?
 */
function display_anonymous_right_menu()
{
	global $loginFailed, $_plugins, $_user;

	$platformLanguage = api_get_setting('platformLanguage');

	if ( !($_user['user_id']) ) // only display if the user isn't logged in
	{
		api_display_language_form();
		echo '<br />';
		display_login_form();

		if ($loginFailed)
		{
			handle_login_failed();
		}
		if (api_get_setting('allow_lostpassword') == 'true' OR api_get_setting('allow_registration') == 'true')
		{
			echo '<div class="menusection"><span class="menusectioncaption">'.get_lang('MenuUser').'</span><ul class="menulist">';
			if (get_setting('allow_registration') <> 'false')
			{
				echo '<li><a href="main/auth/inscription.php">'.get_lang('Reg').'</a></li>';
			}
			if (get_setting('allow_lostpassword') == 'true')
			{
				display_lost_password_info();
			}
			echo '</ul></div>';
		}
		if(api_number_of_plugins('loginpage_menu') > 0)
		{
			echo '<div class="note" style="background: none">';
			api_plugin('loginpage_menu');
			echo '</div>';
		}
	}

	/*** hide right menu "general" and other parts on anonymous right menu  *****/
	echo "<div class=\"menusection\">", "<span class=\"menusectioncaption\">".get_lang("MenuGeneral")."</span>";
	 echo "<ul class=\"menulist\">";

	$user_selected_language = api_get_interface_language();
	if (!isset ($user_selected_language))
		$user_selected_language = $platformLanguage;
	if(!file_exists('home/home_menu_'.$user_selected_language.'.html'))
	{
		include ('home/home_menu.html');
	}
	else
	{
		include('home/home_menu_'.$user_selected_language.'.html');
	}
	echo '</ul>';
	echo '</div>';

	if ($_user['user_id'] && api_number_of_plugins('campushomepage_menu') > 0)
	{
		echo '<div class="note" style="background: none">';
		api_plugin('campushomepage_menu');
		echo '</div>';
	}
	
	/**
	 * User section
	 */
	if(isset($_SESSION['_user']['user_id']) && $_SESSION['_user']['user_id']!=0){
		echo "<div class=\"menusection\">";
		echo "<span class=\"menusectioncaption\">".get_lang("MenuUser")."</span>";
		echo "<ul class=\"menulist\">";
		
		$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION["studentview"] != "studentenview");
		if ($display_add_course_link)
			display_create_course_link();
		display_edit_course_list_links();
		
		$navigation=array();
		// Link to my profile
		$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
		$navigation['myprofile']['title'] = get_lang('ModifyProfile');
		// Link to my agenda
		$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
		$navigation['myagenda']['title'] = get_lang('MyAgenda');
		
		foreach($navigation as $section => $navigation_info)
		{
			$current = ($section == $GLOBALS['this_section'] ? ' id="current"' : '');
			echo '<li'.$current.'>';
			echo '<a href="'.$navigation_info['url'].'" target="_top">'.$navigation_info['title'].'</a>';
			echo '</li>';
			echo "\n";
		}
		
		echo "</ul>";
		echo "</div>";
	}

/**** use this comment to hide notice file section from right menu ****

	echo '<div class="note">';
	// includes for any files to be displayed below anonymous right menu
	if(!file_exists('home/home_notice_'.$user_selected_language.'.html'))
	{
		include ('home/home_notice.html');
	}
	else
	{
		include('home/home_notice_'.$user_selected_language.'.html');
	}
	echo '</div>';

**** end of hide various right menu items on anonymous right menu ****/
}

/**
*	Reacts on a failed login:
*	displays an explanation with
*	a link to the registration form.
*
*	@version 1.0.1
*/
function handle_login_failed()
{
	switch ($_GET['error'])
	{
		case '':
			$message = get_lang("InvalidId");
			if (api_is_self_registration_allowed())
			{
				$message = get_lang("InvalidForSelfRegistration");
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
	$form->addElement('text','login',get_lang('UserName'),array('size'=>15));
	$form->addElement('password','password',get_lang('Pass'),array('size'=>15));
	$form->addElement('submit','submitAuth',get_lang('Ok'));
	$renderer =& $form->defaultRenderer();
	$renderer->setElementTemplate('<div><label>{label}</label></div><div>{element}</div>');
	$form->display();
}
/**
 * Displays a link to the lost password section
 */
function display_lost_password_info()
{
	echo "<li><a href=\"main/auth/lostPassword.php\">".get_lang("LostPassword")."</a></li>";
}

/**
* Display list of courses in a category.
* (for anonymous users)
*
* @version 1.1
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
*/
function display_anonymous_course_list()
{
	//init
	$web_course_path = api_get_path(WEB_COURSE_PATH);
	$category = $_GET["category"];

	// Database table definitions
	$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
	$main_category_table 	= Database :: get_main_table(TABLE_MAIN_CATEGORY);

	$platformLanguage = api_get_setting('platformLanguage');

	//get list of courses in category $category
	$sql_get_course_list = "SELECT * FROM $main_course_table cours
								WHERE category_code = '".mysql_real_escape_string($_GET["category"])."'
								ORDER BY UPPER(visual_code)";
	//removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
	$sql_result_courses = api_sql_query($sql_get_course_list, __FILE__, __LINE__);

	while ($course_result = mysql_fetch_array($sql_result_courses))
	{
		$course_list[] = $course_result;
	}

	$sqlGetSubCatList = "
				SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
				FROM $main_category_table t1
				LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
				LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code AND t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."')
				WHERE t1.parent_id ". (empty ($category) ? "IS NULL" : "='$category'")."
				GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos";
	$resCats = api_sql_query($sqlGetSubCatList, __FILE__, __LINE__);
	$thereIsSubCat = FALSE;
	if (mysql_num_rows($resCats) > 0)
	{
		$htmlListCat = "<h4 style=\"margin-top: 0px;\">".get_lang("CatList")."</h4>"."<ul>";
		while ($catLine = mysql_fetch_array($resCats))
		{
			if ($catLine['code'] != $category)
			{
				$htmlListCat .= "<li>";

				$category_has_open_courses = category_has_open_courses($catLine['code']);
				if ($category_has_open_courses)
				{
					//the category contains courses accessible to anonymous visitors
					$htmlListCat .= "<a href=\"".api_get_self()."?category=".$catLine['code']."\">".$catLine['name']."</a>";
					if (get_setting('show_number_of_courses') == 'true')
					{
						$htmlListCat .= " (".$catLine['nbCourse']." ".get_lang("Courses").")";
					}
				}
				elseif ($catLine['children_count'] > 0)
				{
					//the category has children, subcategories
					$htmlListCat .= "<a href=\"".api_get_self()."?category=".$catLine['code']."\">".$catLine['name']."</a>";
				}
				/************************************************************************
				 end changed code to eliminate the (0 courses) after empty categories
				 ************************************************************************/
				elseif (get_setting('show_empty_course_categories') == 'true')
				{
					$htmlListCat .= $catLine['name'];
				}
				$htmlListCat .= "</li>\n";
				$thereIsSubCat = true;
			}
			else
			{
				$htmlTitre = "<p>";
				if ($_setting['show_back_link_on_top_of_tree'] == 'true')
				{
					$htmlTitre .= "<a href=\"".api_get_self()."\">"."&lt;&lt; ".get_lang("BackToHomePage")."</a>";
				}
				if (!is_null($catLine['parent_id']) || ($_setting['show_back_link_on_top_of_tree'] <> 'true' && !is_null($catLine['code'])))
				{
					$htmlTitre .= "<a href=\"".api_get_self()."?category=".$catLine['parent_id']."\">"."&lt;&lt; ".get_lang("Up")."</a>";
				}
				$htmlTitre .= "</p>\n";
				if ($category != "" && !is_null($catLine['code']))
				{
					$htmlTitre .= "<h3>".$catLine['name']."</h3>\n";
				}
				else
				{
					$htmlTitre .= "<h3>".get_lang("Categories")."</h3>\n";
				}
			}
		}
		$htmlListCat .= "</ul>\n";
	}
	echo $htmlTitre;
	if ($thereIsSubCat)
	{
		echo $htmlListCat;
	}
	while ($categoryName = mysql_fetch_array($resCats))
	{
		echo "<h3>", $categoryName['name'], "</h3>\n";
	}
	$numrows = mysql_num_rows($sql_result_courses);
	if ($numrows > 0)
	{
		if ($thereIsSubCat)
		{
			echo "<hr size=\"1\" noshade=\"noshade\">\n";
		}
		echo "<h4 style=\"margin-top: 0px;\">", get_lang("CourseList"), "</h4>\n", "<ul>\n";
		foreach ($course_list AS $course)
		{
			echo "<li>\n";
			echo "<a href=\"".$web_course_path.$course['directory'], "/\">", $course['title'], "</a>";
			echo "<br/>", $course['visual_code'], " - ", $course['tutor_name'];
			if ($_setting['show_different_course_language'] == 'true' && $course['course_language'] <> api_get_setting('platformLanguage'))
			{
				echo ' - '.$course['course_language'];
			}
			echo "</li>\n";
		}
		echo "</ul>\n";
	}
	else
	{
		// echo "<blockquote>",get_lang('_No_course_publicly_available'),"</blockquote>\n";
	}
	if ($category != "")
	{
		echo "<p>", "<a href=\"".api_get_self()."\"><b>&lt;&lt;</b> ", get_lang("BackToHomePage"), "</a>", "</p>\n";
	}
}
?>