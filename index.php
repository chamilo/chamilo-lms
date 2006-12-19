<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
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

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This is the index file displayed when a user arrives at Dokeos.
*
*	It dispalys:
*	- tree of courses and categories
*	- login form
*	- public menu
*
*	Search for
*	CONFIGURATION parameters
*	to modify settings
*
*	@todo rewrite code to separate display, logic, database code
*	@package dokeos.main
==============================================================================
*/

/**
 * @todo shouldn't the SCRIPTVAL_ and CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for? 
 * 		 if these are really configuration settings then we can add those to the dokeos config settings
 */

/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
// only this script should have this constant defined
define('DOKEOS_HOMEPAGE', true);
// Don't change these settings
define("SCRIPTVAL_No", 0);
define("SCRIPTVAL_InCourseList", 1);
define("SCRIPTVAL_UnderCourseList", 2);
define("SCRIPTVAL_Both", 3);
define("SCRIPTVAL_NewEntriesOfTheDay", 4);
define("SCRIPTVAL_NewEntriesOfTheDayOfLastLogin", 5);
define("SCRIPTVAL_NoTimeLimit", 6);
// End 'don't change' section

// name of the language file that needs to be included 
$language_file = array ('courses', 'index');
$cidReset = true; /* Flag forcing the 'current course' reset,
                   as we're not inside a course anymore  */
/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
include_once ('./main/inc/global.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// the section (for the tabs)
$this_section = SECTION_CAMPUS;

/*
-----------------------------------------------------------
	Action Handling
-----------------------------------------------------------
*/
if ($_GET['logout'])
{
	$query_string='';

	if(!empty($_SESSION['user_language_choice']))
	{
		$query_string='?language='.$_SESSION['user_language_choice'];
	}
	
	
	$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
	
	$sql_last_connection="SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='".$_GET["uid"]."' ORDER BY login_date DESC LIMIT 0,1";

	$q_last_connection=mysql_query($sql_last_connection);
	$i_id_last_connection=mysql_result($q_last_connection,0,"login_id");
	
	$s_sql_update_logout_date="UPDATE $tbl_track_login SET logout_date=NOW() WHERE login_id='$i_id_last_connection'";

	api_sql_query($s_sql_update_logout_date);
	

	//LoginDelete(".$_user['user_id'].", $_configuration['statistics_database']);
	LoginDelete($_GET["uid"], $_configuration['statistics_database']);
	api_session_destroy();

	header("Location: index.php$query_string");
	exit();
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
// @todo shouldn't these be moved to the config page or made into dokeos config settings?
// ---- Category list options ----
/** defines wether or not anonymous visitors can see a list of the courses on
the Dokeos homepage that are open to the world */
define('DISPLAY_COURSES_TO_ANONYMOUS_USERS', true);
define('CONFVAL_showNodeEmpty', true);
define('CONFVAL_showNumberOfChild', false); // actually count are only for direct children
define('CONFVAL_ShowLinkBackToTopOfTree', false);
// ---- Course list options ----
define("CONFVAL_showCourseLangIfNotSameThatPlatform", TRUE);
// Preview of course content
// to disable all: set CONFVAL_maxTotalByCourse = 0
// to enable all: set e.g. CONFVAL_maxTotalByCourse = 5
// by default disabled since what's new icons are better (see function display_digest() )
define("CONFVAL_maxValvasByCourse", 2); // Maximum number of entries
define("CONFVAL_maxAgendaByCourse", 2); //  collected from each course
define("CONFVAL_maxTotalByCourse", 0); //  and displayed in summary.
define("CONFVAL_NB_CHAR_FROM_CONTENT", 80);
// Order to sort data
$orderKey = array('keyTools', 'keyTime', 'keyCourse'); // default "best" Choice
//$orderKey = array('keyTools', 'keyCourse', 'keyTime');
//$orderKey = array('keyCourse', 'keyTime', 'keyTools');
//$orderKey = array('keyCourse', 'keyTools', 'keyTime');
define('CONFVAL_showExtractInfo', SCRIPTVAL_UnderCourseList);
// SCRIPTVAL_InCourseList    // best choice if $orderKey[0] == 'keyCourse'
// SCRIPTVAL_UnderCourseList // best choice
// SCRIPTVAL_Both // probably only for debug
//$dateFormatForInfosFromCourses = $dateFormatShort;
$dateFormatForInfosFromCourses = $dateFormatLong;
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NewEntriesOfTheDay);
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NoTimeLimit);
define("CONFVAL_limitPreviewTo", SCRIPTVAL_NewEntriesOfTheDayOfLastLogin);
if (isset ($_user['user_id']))
{
	$nameTools = api_get_setting('siteName');
}

/*
-----------------------------------------------------------
	Check configuration parameters integrity
-----------------------------------------------------------
*/
if (CONFVAL_showExtractInfo != SCRIPTVAL_UnderCourseList and $orderKey[0] != "keyCourse")
{
	// CONFVAL_showExtractInfo must be SCRIPTVAL_UnderCourseList to accept $orderKey[0] !="keyCourse"
	if (DEBUG || api_is_platform_admin()) // Show bug if admin. Else force a new order
		die("
					<strong>
					config error:".__FILE__."</strong>
					<br/>
					set
					<ul>
						<li>
							CONFVAL_showExtractInfo=SCRIPTVAL_UnderCourseList
							(actually : ".CONFVAL_showExtractInfo.")
						</li>
					</ul>
					or
					<ul>
						<li>
							\$orderKey[0] !=\"keyCourse\"
							(actually : ".$orderKey[0].")
						</li>
					</ul>");
	else
	{
		$orderKey = array ("keyCourse", "keyTools", "keyTime");
	}
}
/*
==============================================================================
		LOGIN
==============================================================================
*/
if ($_GET["submitAuth"] == 1)
{
	// nice lie!!!
	echo "Attempted breakin - sysadmins notified.";
	session_destroy();
	die();
}
if ($_POST["submitAuth"])
{
	// To ensure legacy compatibility, we set the following variables.
	// But they should be removed at last.
	$lastname		 	= $_user['lastName'];
	$firstname	 		= $_user['firstName'];
	$email			 	= $_user['mail'];
	$status			 	= $uData['status'];
	if (isset ($_user['user_id']))
	{
		$sqlLastLogin = "SELECT UNIX_TIMESTAMP(login_date)
								FROM $track_login_table
								WHERE login_user_id = '".$_user['user_id']."'
								ORDER BY login_date DESC LIMIT 1";
		$resLastLogin = api_sql_query($sqlLastLogin, __FILE__, __LINE__);
		if (!$resLastLogin)
			if (mysql_num_rows($resLastLogin) > 0)
			{
				$user_last_login_datetime = mysql_fetch_array($resLastLogin);
				$user_last_login_datetime = $user_last_login_datetime[0];
				api_session_register('user_last_login_datetime');
			}
		mysql_free_result($resLastLogin);
		//event_login();
		if (api_is_platform_admin())
		{
			// decode all open event informations and fill the track_c_* tables
			include (api_get_path(LIBRARY_PATH)."stats.lib.inc.php");
			decodeOpenInfos();
		}
	}
} // end login -- if($submit)
else
{
	// only if login form was not sent because if the form is sent the user was
	// already on the page.
	event_open();
}
/*
-----------------------------------------------------------
	Header
	include the HTTP, HTML headers plus the top banner
-----------------------------------------------------------
*/

$help = "Clar";

Display :: display_header('', $help);

/*
==============================================================================
		FUNCTIONS

		display_anonymous_right_menu()
		display_anonymous_course_list()

		display_login_form()
		handle_login_failed()

		display_lost_password_info()
==============================================================================
*/

/*
-----------------------------------------------------------
	Display functions
-----------------------------------------------------------
*/

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
		echo '<div class="note" style="background: none">';
		api_plugin('loginpage_menu');
		echo '</div>';
	}

	/*** hide right menu "general" and other parts on anonymous right menu  *****/
	echo "<div class=\"menusection\">", "<span class=\"menusectioncaption\">".get_lang("MenuGeneral")."</span>";
	 echo "<ul class=\"menulist\">";

	$user_selected_language = $_SESSION["user_language_choice"];
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

	if ($_user['user_id'])
	{
		echo '<div class="note" style="background: none">';
		api_plugin('campushomepage_menu');
		echo '</div>';
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
	$element_template = '<!-- BEGIN required --><span class="form_required">*</span> <!-- END required -->{label}<br />
			<!-- BEGIN error --><span class="form_error">{error}</span><br /><!-- END error -->	{element}<br />';
	$renderer->setElementTemplate($element_template);
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
* Warning: this function defines globals.
* @version 1.0
*/
function display_anonymous_course_list()
{
	//init
	$web_course_path = api_get_path(WEB_COURSE_PATH);
	$category = $_GET["category"];
	$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$main_category_table = Database :: get_main_table(TABLE_MAIN_CATEGORY);
	$platformLanguage = api_get_setting('platformLanguage');

	//get list of courses in category $category
	$sql_get_course_list = "SELECT * FROM $main_course_table cours
								WHERE category_code = '".$category."'
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
					$htmlListCat .= "<a href=\"".$_SERVER['PHP_SELF']."?category=".$catLine['code']."\">".$catLine['name']."</a>";
					if (CONFVAL_showNumberOfChild)
					{
						$htmlListCat .= " (".$catLine['nbCourse']." ".get_lang("Courses").")";
					}
				}
				elseif ($catLine['children_count'] > 0)
				{
					//the category has children, subcategories
					$htmlListCat .= "<a href=\"".$_SERVER['PHP_SELF']."?category=".$catLine['code']."\">".$catLine['name']."</a>";
				}
				/************************************************************************
				 end changed code to eliminate the (0 courses) after empty categories
				 ************************************************************************/
				elseif (CONFVAL_showNodeEmpty)
				{
					$htmlListCat .= $catLine['name'];
				}
				$htmlListCat .= "</li>\n";
				$thereIsSubCat = true;
			}
			else
			{
				$htmlTitre = "<p>";
				if (CONFVAL_ShowLinkBackToTopOfTree)
				{
					$htmlTitre .= "<a href=\"".$_SERVER['PHP_SELF']."\">"."&lt;&lt; ".get_lang("BackToHomePage")."</a>";
				}
				if (!is_null($catLine['parent_id']) || (!CONFVAL_ShowLinkBackToTopOfTree && !is_null($catLine['code'])))
				{
					$htmlTitre .= "<a href=\"".$_SERVER['PHP_SELF']."?category=".$catLine['parent_id']."\">"."&lt;&lt; ".get_lang("Up")."</a>";
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
		echo $htmlListCat;
	while ($categoryName = mysql_fetch_array($resCats))
	{
		echo "<h3>", $categoryName['name'], "</h3>\n";
	}
	$numrows = mysql_num_rows($sql_result_courses);
	if ($numrows > 0)
	{
		if ($thereIsSubCat)
			echo "<hr size=\"1\" noshade=\"noshade\">\n";
		echo "<h4 style=\"margin-top: 0px;\">", get_lang("CourseList"), "</h4>\n", "<ul>\n";
		while ($course = mysql_fetch_array($sql_result_courses))
		{
			echo "<li>\n", "<a href=\"".$web_course_path.$course['directory'], "/\">", $course['title'], "</a>", "<br/>", $course['visual_code'], " - ", $course['tutor_name'], ((CONFVAL_showCourseLangIfNotSameThatPlatform && $course['course_language'] != $platformLanguage) ? " - ".$course['course_language'] : ""), "\n", "</li>\n";
		}
		echo "</ul>\n";
	}
	else
	{
		// echo "<blockquote>",get_lang('_No_course_publicly_available'),"</blockquote>\n";
	}
	if ($category != "")
	{
		echo "<p>", "<a href=\"".$_SERVER['PHP_SELF']."\"><b>&lt;&lt;</b> ", get_lang("BackToHomePage"), "</a>", "</p>\n";
	}
}

function category_has_open_courses($category)
{
	$main_course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
	$sql_query = "SELECT * FROM $main_course_table WHERE category_code='$category'";
	$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
	while ($course = mysql_fetch_array($sql_result))
	{
		if ($is_allowed_anonymous_access)
		return true; //at least one open course
	}

	return false;
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

echo '<div class="maincontent">';
/*
-----------------------------------------------------------------------------
	Plugins for loginpage_main AND campushomepage_main
-----------------------------------------------------------------------------
*/
if (!api_get_user_id())
{
	api_plugin('loginpage_main');
}
else
{
	api_plugin('campushomepage_main');
}

// Including the page for the news
if (!empty ($_GET['include']) && !strstr($_GET['include'], '/') && strstr($_GET['include'], '.html'))
{
	include ('./home/'.$_GET['include']);
	$pageIncluded = true;
}
else
{
	if(file_exists('home/home_top_'.$user_selected_language.'.html'))
	{
		include('home/home_top_'.$user_selected_language.'.html');
	}
	else
	{
		$platform_language=api_get_setting("platformLanguage");
		if(file_exists('home/home_top_'.$platform_language.'.html')){
			include('home/home_top_'.$platform_language.'.html');
		}
		else{
			include ('home/home_top.html');
		}
	}
}

// Display System announcements
$announcement = $_GET['announcement'] ? $_GET['announcement'] : -1;
SystemAnnouncementManager :: display_announcements(VISIBLE_GUEST, $announcement);

echo "<br><br><br>";

// Display courses and category list
if (!$pageIncluded)
{
//	echo '<div class="clear">&nbsp;</div>';
	echo '<div class="home_cats">';
	if (DISPLAY_COURSES_TO_ANONYMOUS_USERS)
	{
		display_anonymous_course_list();
	}
	echo '</div>';

	echo '<div class="home_news">';
	$user_selected_language = $_SESSION["_user"]["language"];
	if(file_exists('home/home_news_'.$user_selected_language.'.html'))
	{
		include ('home/home_news_'.$user_selected_language.'.html');
	}
	else
	{
		$platform_language=api_get_setting("platformLanguage");
		if(file_exists('home/home_news_'.$platform_language.'.html')){
			include('home/home_news_'.$platform_language.'.html');
		}
		else{
			include ('home/home_news.html');
		}
	}
	echo '</div>';

}
echo '</div>';

// Right Menu
// language form, login section + useful weblinks
echo '<div class="menu">';
display_anonymous_right_menu();
echo '</div>';
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>