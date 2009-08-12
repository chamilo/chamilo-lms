<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This script contains the actual html code to display the "header"
*	or "banner" on top of every Dokeos page.
*
*	@package dokeos.include
==============================================================================
*/
?>
<div id="wrapper">

<div id="header">
	<div id="header1">
		<div id="top_corner"></div> 
		<div id="institution">
			<a href="<?php echo api_get_path(WEB_PATH);?>index.php" target="_top"><?php echo api_get_setting('siteName') ?></a>
			<?php
			$iurl = api_get_setting('InstitutionUrl');
			$iname = api_get_setting('Institution');
			if (!empty($iname))
			{
	           echo '-&nbsp;<a href="'.$iurl.'" target="_top">'.$iname.'</a>';
			}
			?>
		</div>
<?php
/*
-----------------------------------------------------------------------------
	Course title section
-----------------------------------------------------------------------------
*/
if (!empty($_cid) and $_cid != -1 and isset($_course)) {
	//Put the name of the course in the header
?>
	<div id="my_courses"><a href="<?php echo api_get_path(WEB_COURSE_PATH).$_course['path']; ?>/index.php" target="_top">
<?php
	echo $_course['name']." ";
	/*echo '
		<div id="my_courses">
			<a href="'.api_get_path(WEB_COURSE_PATH).$_course['path'].'/index.php" target="_top">'.$_course['name'].' ';*/
	if (api_get_setting("display_coursecode_in_courselist") == "true") {
		echo $_course['official_code'];
	}

	if(api_get_setting("use_session_mode") == "true" && isset($_SESSION['session_name'])) { 
		echo ' ('.$_SESSION['session_name'].')';
	}
	if (api_get_setting("display_coursecode_in_courselist") == "true" AND api_get_setting("display_teacher_in_courselist") == "true") {
		echo " - ";
	}
	if (api_get_setting("display_teacher_in_courselist") == "true") {
		echo stripslashes($_course['titular']);
	}
	echo "</a></div>";
} elseif (isset ($nameTools) && $language_file != 'course_home') {
	//Put the name of the user-tools in the header
	if (!isset ($_user['user_id'])) {
		echo " ";
	} elseif(!$noPHP_SELF) {
		echo "<div id=\"my_courses\"><a href=\"".api_get_self()."?".api_get_cidreq(), "\" target=\"_top\">", $nameTools, "</a></div>", "\n";
	} else {
		echo "<div id=\"my_courses\">$nameTools</div>\n";
	}
}
//not to let the header disappear if there's nothing on the left
echo '		<div class="clear">&nbsp;</div>';

/*
-----------------------------------------------------------------------------
	Plugins for banner section
-----------------------------------------------------------------------------
*/
api_plugin('header');
$web_course_path = api_get_path(WEB_COURSE_PATH);

/*
-----------------------------------------------------------------------------
	External link section
-----------------------------------------------------------------------------
*/
if (isset($_course['extLink']) && $_course['extLink']['name'] != "") {
	echo "<span class=\"extLinkSeparator\"> / </span>";
	if ($_course['extLink']['url'] != "") {
		echo "<a class=\"extLink\" href=\"".$_course['extLink']['url']."\" target=\"_top\">";
		echo $_course['extLink']['name'];
		echo "</a>";
	} else {
		echo $_course['extLink']['name'];
	}
		
}
?>
	</div>
	<div id="header2">
		<div id="Header2Right">
			<ul>
<?php 
if ((api_get_setting('showonline','world') == "true" AND !$_user['user_id']) OR (api_get_setting('showonline','users') == "true" AND $_user['user_id']) OR (api_get_setting('showonline','course') == "true" AND $_user['user_id'] AND $_cid)) {
	if (api_get_setting("use_session_mode") == "true" && isset($_user['user_id']) && api_is_coach()) {
	    echo '  <li><a href="'.api_get_path(WEB_PATH).'whoisonlinesession.php?id_coach='.$_user['user_id'].'&amp;referer='.urlencode($_SERVER['REQUEST_URI']).'" target="_top">'.get_lang('UsersConnectedToMySessions').'</a></li>';
	}

	$statistics_database = Database :: get_statistic_database();
	$number = count(WhoIsOnline(api_get_user_id(), $statistics_database, api_get_setting('time_limit_whosonline')));
	if(!empty($_course['id'])) {
		$online_in_course = who_is_online_in_this_course(api_get_user_id(), api_get_setting('time_limit_whosonline'), $_course['id']);
		$number_online_in_course= count( $online_in_course );
	} else {
		$number_online_in_course = 0;
	}
	
 	echo "<li>";
	// Display the who's online of the platform
	if ((api_get_setting('showonline','world') == "true" AND !$_user['user_id']) OR (api_get_setting('showonline','users') == "true" AND $_user['user_id'])) {
		//echo '<a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_top">'.$number.'</a>';
		echo '<a href="'.api_get_path(WEB_PATH).'whoisonline.php" target="_top">'.get_lang('UsersOnline').': '.$number.'</a>';
	}

	// Display the who's online for the course
	if (is_array($_course) AND api_get_setting('showonline','course') == "true" AND isset($_course['sysCode'])) {
		echo "(<a href='".api_get_path(WEB_PATH)."whoisonline.php?cidReq=".$_course['sysCode']."' target='_top'>$number_online_in_course ".get_lang('InThisCourse')."</a>)";
	}
	echo '</li>';
}

if ($_user['user_id'] && isset($_cid)) {
	if ((api_is_course_admin() || api_is_platform_admin()) && api_get_setting('student_view_enabled') == 'true') {
		echo '<li>| ';
		api_display_tool_view_option();
		echo '</li>';
	}
}
if ( api_is_allowed_to_edit() ) {
	if(!empty($help)) {
	// Show help
	?>
	<li>|
	<a href="#" onclick="MyWindow=window.open('<?php echo api_get_path(WEB_CODE_PATH)."help/help.php"; ?>?open=<?php echo $help; ?>','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=620,height=600,left=200,top=20'); return false;">
	<img src="<?php echo api_get_path(WEB_CODE_PATH); ?>img/khelpcenter.gif" style="vertical-align: middle;" alt="<?php echo get_lang("Help") ?>"/>&nbsp;<?php echo get_lang("Help") ?></li></a>

	<?php
	}
}
?>
		</ul>
	</div>
		<div class="clear">&nbsp;</div>
	</div>
	
	<div id="header3">
<?php
/*
-----------------------------------------------------------------------------
	User section
-----------------------------------------------------------------------------
*/
if ($_user['user_id']) {
	$login = '';
	if(api_is_anonymous()) {
		$login = '('.get_lang('Anonymous').')';
	} else {
		$uinfo = api_get_user_info(api_get_user_id());
		$login = '('.$uinfo['username'].')';	
	}
	?>
	 <!-- start user section line with name, my course, my profile, scorm info, etc -->

	 <ul id="logout">
				<li><a href="<?php echo api_get_path(WEB_PATH); ?>index.php?logout=logout&uid=<?php echo $_user['user_id']; ?>" target="_top"><span><?php echo get_lang('Logout').' '.$login; ?></span></a></li>
	 </ul>
<?php
}
?>
		<ul>
<?php
$navigation = array();

$possible_tabs = get_tabs();

// Campus Homepage
if (api_get_setting('show_tabs', 'campus_homepage') == 'true') {
	$navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
} else {
	$menu_navigation[SECTION_CAMPUS] = $possible_tabs[SECTION_CAMPUS];
}

if ($_user['user_id'] && !api_is_anonymous()) {
	// My Courses
	if (api_get_setting('show_tabs', 'my_courses') == 'true') {
		$navigation['mycourses'] = $possible_tabs['mycourses'];
	} else{
		$menu_navigation['mycourses'] = $possible_tabs['mycourses'];
	}

	// My Profile
	if (api_get_setting('show_tabs', 'my_profile') == 'true') {
		$navigation['myprofile'] = $possible_tabs['myprofile'];
	} else {
		$menu_navigation['myprofile'] = $possible_tabs['myprofile'];
	}

	// My Agenda
	if (api_get_setting('show_tabs', 'my_agenda') == 'true') {
		$navigation['myagenda'] = $possible_tabs['myagenda'];
	} else {
		$menu_navigation['myagenda'] = $possible_tabs['myagenda'];
	}

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		if (api_get_setting('show_tabs', 'my_gradebook') == 'true') {
			$navigation['mygradebook'] = $possible_tabs['mygradebook'];
		} else{
			$menu_navigation['mygradebook'] = $possible_tabs['mygradebook'];
		}
	}
	
	// Reporting
	if (api_get_setting('show_tabs', 'reporting') == 'true') {
		if(api_is_allowed_to_create_course() || $_user['status'] == DRH) {
			$navigation['session_my_space'] = $possible_tabs['session_my_space'];
		} else {
			$navigation['session_my_space'] = $possible_tabs['session_my_progress'];
		}
	} else {
		if(api_is_allowed_to_create_course() || $_user['status'] == DRH) {
			$menu_navigation['session_my_space'] = $possible_tabs['session_my_space'];
		} else {
			$menu_navigation['session_my_space'] = $possible_tabs['session_my_progress'];
		}
	}
	
	
	if(api_is_platform_admin(true)) {
		if (api_get_setting('show_tabs', 'platform_administration') == 'true') {
			$navigation['platform_admin'] = $possible_tabs['platform_admin'];
		} else {
			$menu_navigation['platform_admin'] = $possible_tabs['platform_admin'];
		}
	}
}

// Displaying the tabs
foreach($navigation as $section => $navigation_info) {
	if(isset($GLOBALS['this_section'])) {
		$current = ($section == $GLOBALS['this_section'] ? ' id="current"' : '');
	} else {
		$current = '';
	}
	echo '<li'.$current.'><a href="'.$navigation_info['url'].'" target="_top"><span>'.$navigation_info['title'].'</span></a></li>'."\n";
}
?>
		</ul>
		<div style="clear: both;" class="clear"> </div>
	</div>	
<?php
/*
 * if the user is a coach he can see the users who are logged in its session
 */
$navigation = array();
// part 1: Course Homepage. If we are in a course then the first breadcrumb is a link to the course homepage
//hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
$session_id     = api_get_session_id();
$session_name   = api_get_session_name($my_session_id);
$my_session_name= ($session_name==null) ? '' : '&nbsp;('.$session_name.')';
if (isset ($_cid) and $_cid!=-1 and isset($_course) and !isset($_GET['hide_course_breadcrumb'])) {
	$navigation_item['url'] = $web_course_path . $_course['path'].'/index.php';
	switch(api_get_setting('breadcrumbs_course_homepage')) {
		case 'get_lang':
			$navigation_item['title'] =  get_lang('CourseHomepageLink');
			break;
		case 'course_code':
			$navigation_item['title'] =  $_course['official_code'];
			break;
		case 'session_name_and_course_title':
			$navigation_item['title'] =  $_course['name'].$my_session_name;
			break;
		default:
			$navigation_item['title'] =  $_course['name'];
			break;
	}
	$navigation[] = $navigation_item;
}
// part 2: Interbreadcrumbs. If there is an array $interbreadcrumb defined then these have to appear before the last breadcrumb (which is the tool itself)
if (isset($interbreadcrumb) && is_array($interbreadcrumb)) {
	foreach($interbreadcrumb as $breadcrumb_step) {
		$sep = (strrchr($breadcrumb_step['url'], '?') ? '&amp;' : '?');
		$navigation_item['url'] = $breadcrumb_step['url'].$sep.api_get_cidreq();
		$navigation_item['title'] = $breadcrumb_step['name'];
		$navigation[] = $navigation_item;
	}
}
// part 3: The tool itself. If we are on the course homepage we do not want to display the title of the course because this
// is the same as the first part of the breadcrumbs (see part 1)
if (isset ($nameTools) AND $language_file<>"course_home") {
	$navigation_item['url'] = '#';
	$navigation_item['title'] = $nameTools;
	$navigation[] = $navigation_item;
}

$final_navigation = array();
foreach($navigation as $index => $navigation_info) {
	if(!empty($navigation_info['title'])) {
		$final_navigation[$index] = '<a href="'.$navigation_info['url'].'" class="breadcrumb breadcrumb'.$index.'" target="_top">'.$navigation_info['title'].'</a>';
	}
}

if (!empty($final_navigation)) {
	echo '<div id="header4">';	
	echo implode(' &gt; ',$final_navigation);
	echo '</div>';
}
if(api_get_setting('show_toolshortcuts')=='true') {
	echo '<div id="toolshortcuts">';
	require_once('tool_navigation_menu.inc.php');
 	show_navigation_tool_shortcuts();
  	echo '</div>';
}

if (isset ($dokeos_database_connection)) {
	// connect to the main database.
	// if single database, don't pefix table names with the main database name in SQL queries
	// (ex. SELECT * FROM `table`)
	// if multiple database, prefix table names with the course database name in SQL queries (or no prefix if the table is in
	// the main database)
	// (ex. SELECT * FROM `table_from_main_db`  -  SELECT * FROM `courseDB`.`table_from_course_db`)
	mysql_select_db($_configuration['main_database'], $dokeos_database_connection);
}
?>

</div> <!-- end of the whole #header section -->
<div class="clear">&nbsp;</div>
<?php
//to mask the main div, set $header_hide_main_div to true in any script just before calling Display::display_header();
global $header_hide_main_div;
if (!empty($header_hide_main_div) && $header_hide_main_div===true) {
	//do nothing
} else {
?>
<div id="main"> <!-- start of #main wrapper for #content and #menu divs -->
<?php
}
/*
-----------------------------------------------------------------------------
	"call for chat" module section
-----------------------------------------------------------------------------
*/
$chat = strpos(api_get_self(), 'chat_banner.php');
if (!$chat) {
	include_once (api_get_path(LIBRARY_PATH)."online.inc.php");
	//echo $accept;
	$chatcall = chatcall();
	if ($chatcall) {
		Display :: display_normal_message($chatcall);
	}
}

/*
-----------------------------------------------------------------------------
	Navigation menu section
-----------------------------------------------------------------------------
*/
if(api_get_setting('show_navigation_menu') != 'false' && api_get_setting('show_navigation_menu') != 'icons') {
	Display::show_course_navigation_menu($_GET['isHidden']);
	if (isset($_cid) ) {
		echo '<div id="menuButton">';
 		echo $output_string_menu;
 		echo '</div>';
		if(isset($_SESSION['hideMenu'])) {
			if($_SESSION['hideMenu'] =="shown") {
 				if (isset($_cid) ) {
					echo '<div id="centerwrap"> <!-- start of #centerwrap -->';
					echo '<div id="center"> <!-- start of #center -->';
				}
			}
 		} else {
			if (isset($_cid) ) {
				echo '<div id="centerwrap"> <!-- start of #centerwrap -->';
				echo '<div id="center"> <!-- start of #center -->';
			}
 		}
 	}
}

/**
 * Determines the possible tabs (=sections) that are available.
 * This function is used when creating the tabs in the third header line and all the sections
 * that do not appear there (as determined by the platform admin on the Dokeos configuration settings page)
 * will appear in the right hand menu that appears on several other pages
 *
 * @return array containing all the possible tabs
 *
 * @version Dokeos 1.8.4
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function get_tabs() {
	global $_course, $rootAdminWeb, $_user;

	// Campus Homepage
	$navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
	$navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');

	// My Courses
	if(api_get_setting('use_session_mode')=='true') {
		if(api_is_allowed_to_create_course()) {
			// Link to my courses for teachers
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		} else {
			// Link to my courses for students
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		}

	} else {
		// Link to my courses
		$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
		$navigation['mycourses']['title'] = get_lang('MyCourses');
	}

	// My Profile
	$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myprofile']['title'] = get_lang('ModifyProfile');

	// Link to my agenda
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myagenda']['title'] = get_lang('MyAgenda');

	// Gradebook
	if (api_get_setting('gradebook_enable') == 'true') {
		$navigation['mygradebook']['url'] = api_get_path(WEB_CODE_PATH).'gradebook/gradebook.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );	
		$navigation['mygradebook']['title'] = get_lang('MyGradebook');
	}

	// Reporting
	if(api_is_allowed_to_create_course() || $_user['status']==DRH) {
		// Link to my space
		$navigation['session_my_space']['url'] = api_get_path(WEB_CODE_PATH).'mySpace/';
		$navigation['session_my_space']['title'] = get_lang('MySpace');
	} else {
		// Link to my progress
		$navigation['session_my_progress']['url'] = api_get_path(WEB_CODE_PATH).'auth/my_progress.php';
		$navigation['session_my_progress']['title'] = get_lang('MyProgress');
	}

	// Platform administration
	if (api_is_platform_admin(true)) {
		//$navigation['platform_admin']['url'] = $rootAdminWeb;
		$navigation['platform_admin']['url'] = api_get_path(WEB_CODE_PATH).'admin/';
		$navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
	}

	return $navigation;
}
?>
<!--   Begin Of script Output   -->
