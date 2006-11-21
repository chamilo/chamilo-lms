<?php
/**
==============================================================================
*	This script contains the actual html code to display the "header"
*	or "banner" on top of every Dokeos page.
*
*	@package dokeos.include
==============================================================================
*/
?>
<div id="header">  <!-- header section start -->
<div id="header1"> <!-- top of banner with institution name/hompage link -->

<div id="institution">
<a href="<?php echo api_get_path(WEB_PATH);?>index.php" target="_top"><?php echo api_get_setting('siteName') ?></a>
-
<a href="<?php echo api_get_setting('InstitutionUrl') ?>" target="_top"><?php echo api_get_setting('Institution') ?></a>
</div>

<?php
/*
-----------------------------------------------------------------------------
	Course title section
-----------------------------------------------------------------------------
*/
if (isset ($_cid))
{
	//Put the name of the course in the header
	?>
	<div id="my_courses"><a href="<?php echo api_get_path(WEB_COURSE_PATH).$_course['path']; ?>/index.php" target="_top">
	<?php

	echo $_course['name']." ";
	if (api_get_setting("display_coursecode_in_courselist") == "true")
	{
		echo $_course['official_code'];
	}

	if(api_get_setting("use_session_mode") == "true" && isset($_SESSION['session_name']))
	{
		echo ' ('.$_SESSION['session_name'].')';
	}
	if (api_get_setting("display_coursecode_in_courselist") == "true" AND api_get_setting("display_teacher_in_courselist") == "true")
	{
		echo " - ";
	}
	if (api_get_setting("display_teacher_in_courselist") == "true")
	{
		echo $_course['titular'];
	}
	echo "</a></div>";
}
elseif (isset ($nameTools) && $langFile != 'course_home')
{
	//Put the name of the user-tools in the header
	if (!isset ($_user['user_id']))
	{
		echo " ";
	}
	elseif(!$noPHP_SELF)
	{
		echo "<div id=\"my_courses\"><a href=\"".$_SERVER['PHP_SELF']."?".api_get_cidreq(), "\" target=\"_top\">", $nameTools, "</a></div>", "\n";
	}
	else
	{
		echo "<div id=\"my_courses\">$nameTools</div>\n";
	}
}
//not to let the header disappear if there's nothing on the left
 echo '<div class="clear">&nbsp;</div>';

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
if ($_course['extLink']['name'] != "") /* ---  --- */
{
	echo " / ";
	if ($_course['extLink']['url'] != "")
	{
		echo "<a href=\"".$_course['extLink']['url']."\" target=\"_top\">";
		echo $_course['extLink']['name'];
		echo "</a>";
	}
	else
		echo $_course['extLink']['name'];
}
echo "</div> <!-- end of #header1 -->";


echo '<div id="header2">';
echo '<div id="Header2Right">';
echo '<ul>';

if ((api_get_setting('showonline','world') == "true" AND !$_user['user_id']) OR (api_get_setting('showonline','users') == "true" AND $_user['user_id']) OR (api_get_setting('showonline','course') == "true" AND $_user['user_id'] AND $_cid))
{
	if(api_get_setting("use_session_mode") == "true" && isset($_user['user_id']) && api_is_coach())
	{
		echo "<li><a href='".api_get_path(WEB_PATH)."whoisonlinesession.php?id_coach=".$_user['user_id']."&referer=".urlencode($_SERVER['REQUEST_URI'])."' target='_top'>Voir les utilisateurs connectés à mes sessions</a></li>";
	}

	$statistics_database = Database :: get_statistic_database();
	$number = count(WhoIsOnline(api_get_user_id(), $statistics_database, api_get_setting('time_limit_whosonline')));
	$online_in_course = who_is_online_in_this_course(api_get_user_id(), api_get_setting('time_limit_whosonline'), $_course['id']);
	$number_online_in_course= count( $online_in_course );
	echo "<li>".get_lang('UsersOnline').": ";

	// Display the who's online of the platform
	if ((api_get_setting('showonline','world') == "true" AND !$_user['user_id']) OR (api_get_setting('showonline','users') == "true" AND $_user['user_id']))
	{
		echo "<a href='".api_get_path(WEB_PATH)."whoisonline.php' target='_top'>".$number."</a>";
	}

	// Display the who's online for the course
	if ($_course AND api_get_setting('showonline','course') == "true")
	{
		echo "(<a href='".api_get_path(WEB_PATH)."whoisonline.php?cidReq=".$_course['sysCode']."' target='_top'>$number_online_in_course ".get_lang('InThisCourse')."</a>)";
	}


	echo '</li>';
}
if ($_user['user_id'])
{
	if (api_is_course_admin() && is_student_view_enabled())
	{
		echo '<li>|';
		api_display_tool_view_option($_GET['isStudentView']);
		echo '</li>';
	}
}
if ( api_is_allowed_to_edit() )
{
	if( $help != null)
	{
	// Show help
	?>
	<li>|
	<a href="#" onclick="MyWindow=window.open('<?php echo api_get_path(WEB_CODE_PATH)."help/help.php"; ?>?open=<?php echo $help; ?>','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,left=200,top=20'); return false;">
	<img src="<?php echo api_get_path(WEB_CODE_PATH); ?>img/buoy.gif" style="vertical-align: middle;" alt="<?php echo get_lang("Help") ?>"/>&nbsp;<?php echo get_lang("Help") ?></li></a>

	<?php
	}
}
?>
		</ul>
	</div>
<!-- link to campus home (not logged in)
	<a href="<?php echo api_get_path(WEB_PATH); ?>index.php" target="_top"><?php echo api_get_setting('siteName'); ?></a>
 -->
<?php
//not to let the empty header disappear and ensure help pic is inside the header
echo "<div class=\"clear\">&nbsp;</div>";
?>
</div> <!-- End of header 2-->
<div id="header3">
<?php
/*
-----------------------------------------------------------------------------
	User section
-----------------------------------------------------------------------------
*/
if ($_user['user_id'])
{
	?>
	 <!-- start user section line with name, my course, my profile, scorm info, etc -->

	<form method="get" action="<?php echo api_get_path(WEB_PATH); ?>index.php" class="banner_links" target="_top">
	<input type="hidden" name="logout" value="true"/>
	<input type="hidden" name="uid" value="<?php echo $_user['user_id']; ?>"/>
	 <ul id="logout">
	 <li>
	<input type="submit" name="submit" value="<?php echo get_lang("Logout"); ?>"
	onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"
	class="logout" style="	height:20px;" />
	 </li>
	 </ul>
	</form>
<?php
}
echo "<ul>\n";
$navigation = array();
// Link to campus homepage
$navigation[SECTION_CAMPUS]['url'] = api_get_path(WEB_PATH).'index.php';
$navigation[SECTION_CAMPUS]['title'] = get_lang('CampusHomepage');
if ($_user['user_id'])
{
	if(api_get_setting('use_session_mode')=='true')
	{
		if(api_is_allowed_to_create_course())
		{
			// Link to my courses for teachers
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php?nosession=true';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		}
		else 
		{
			// Link to my courses for students
			$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
			$navigation['mycourses']['title'] = get_lang('MyCourses');
		}
		
		// Link to active sessions
		//$navigation[SECTION_ACTIVESESSIONS]['url'] = api_get_path(WEB_PATH).'user_portal.php';
		//$navigation[SECTION_ACTIVESESSIONS]['title'] = get_lang('myActiveSessions');
		// Link to inactive sessions
		//$navigation[SECTION_INACTIVESESSIONS]['url'] = api_get_path(WEB_PATH).'user_portal.php?inactives';
		//$navigation[SECTION_INACTIVESESSIONS]['title'] = get_lang('myInActiveSessions');
		
	}
	else
	{
		// Link to my courses
		$navigation['mycourses']['url'] = api_get_path(WEB_PATH).'user_portal.php';
		$navigation['mycourses']['title'] = get_lang('MyCourses');
	}
	
	//NOW IN THE RIGHT MENU IN "MY COURSES"
	/*
	// Link to my profile
	$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myprofile']['title'] = get_lang('ModifyProfile');
	// Link to my agenda
	$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
	$navigation['myagenda']['title'] = get_lang('MyAgenda');*/
	
	if(api_get_setting('use_session_mode')=='true')
	{
		//It's now in the reporting
		/*if(api_is_coach())
		{
			// Link to my students
			$navigation['session_my_students']['url'] = api_get_path(WEB_PATH).'myStudents.php';
			$navigation['session_my_students']['title'] = get_lang('MyStudents');
		}*/
		if(api_is_allowed_to_create_course())
		{
			// Link to my space
			$navigation['session_my_space']['url'] = api_get_path(WEB_PATH).'main/mySpace/';
			$navigation['session_my_space']['title'] = get_lang('MySpace');
		}
		if(!api_is_allowed_to_create_course())
		{
			// Link to my progress
			$navigation['session_my_progress']['url'] = api_get_path(WEB_PATH).'main/auth/my_progress.php';
			$navigation['session_my_progress']['title'] = get_lang('MyProgress');
		}
	}
	if (api_is_platform_admin())
	{
		// Link to platform admin
		$navigation['platform_admin']['url'] = $rootAdminWeb;
		$navigation['platform_admin']['title'] = get_lang('PlatformAdmin');
	}
}
foreach($navigation as $section => $navigation_info)
{
	$current = ($section == $GLOBALS['this_section'] ? ' id="current"' : '');
	echo '<li'.$current.'>';
	echo '<a href="'.$navigation_info['url'].'" target="_top">'.$navigation_info['title'].'</a>';
	echo '</li>';
	echo "\n";
}
?>
</ul><!-- small hack to have it look good in opera -->&nbsp;
</div> <!-- end of header3 (user) section -->
<?php
/*
-----------------------------------------------------------------------------
	BREADCRUMBS
-----------------------------------------------------------------------------
*/
?>
<div id="header4">
<?php
/*
 * if the user is a coach he can see the users who are logged in its session
 */
$navigation = array();
// part 1: Course Homepage. If we are in a course then the first breadcrumb is a link to the course homepage
if (isset ($_cid))
{
	$navigation_item['url'] = $web_course_path . $_course['path'].'/index.php';
	switch(api_get_setting('breadcrumbs_course_homepage'))
	{
		case 'get_lang':
			$navigation_item['title'] =  get_lang('CourseHomepageLink');
			break;
		case 'course_code':
			$navigation_item['title'] =  $_course['official_code'];
			break;
		default:
			$navigation_item['title'] =  $_course['name'];
			break;
	}
	$navigation[] = $navigation_item;
}
// part 2: Interbreadcrumbs. If there is an array $interbreadcrumb defined then these have to appear before the last breadcrumb (which is the tool itself)
if (is_array($interbreadcrumb))
{
	foreach($interbreadcrumb as $breadcrumb_step)
	{
		$sep = (strrchr($breadcrumb_step['url'], '?') ? '&amp;' : '?');
		$navigation_item['url'] = $breadcrumb_step['url'].$sep.api_get_cidreq();
		$navigation_item['title'] = $breadcrumb_step['name'];
		$navigation[] = $navigation_item;
	}
}
// part 3: The tool itself. If we are on the course homepage we do not want to display the title of the course because this
// is the same as the first part of the breadcrumbs (see part 1)
if (isset ($nameTools) AND $langFile<>"course_home")
{
	$navigation_item['url'] = '#';
	$navigation_item['title'] = $nameTools;
	$navigation[] = $navigation_item;
}

foreach($navigation as $index => $navigation_info)
{
	$navigation[$index] = '<a href="'.$navigation_info['url'].'" target="_top">'.$navigation_info['title'].'</a>';
}
echo implode(' &gt; ',$navigation);
?>
<div class="clear">&nbsp;</div>
</div><!-- end of header4 -->
<?php
if(api_get_setting('show_toolshortcuts')=='true')
{
	echo '<div id="toolshortcuts">';
	require_once('tool_navigation_menu.inc.php');
 	show_navigation_tool_shortcuts();
  	echo '</div>';
}

if (isset ($dokeos_database_connection))
{
	// connect to the main database.
	// if single database, don't pefix table names with the main database name in SQL queries
	// (ex. SELECT * FROM `table`)
	// if multiple database, prefix table names with the course database name in SQL queries (or no prefix if the table is in
	// the main database)
	// (ex. SELECT * FROM `table_from_main_db`  -  SELECT * FROM `courseDB`.`table_from_course_db`)
	mysql_select_db($mainDbName, $dokeos_database_connection);
}
?>

</div> <!-- end of the whole #header section -->
<?php
//to mask the main div, set $header_hide_main_div to true in any script just before calling Display::display_header();
global $header_hide_main_div;
if(!empty($header_hide_main_div) && $header_hide_main_div===true)
{
	//do nothing
}
else
{
?>
<div id="main"> <!-- start of #main wrapper for #content and #menu divs -->
<?php
}
/*
-----------------------------------------------------------------------------
	"call for chat" module section
-----------------------------------------------------------------------------
*/
$chat = strpos($_SERVER['PHP_SELF'], 'chat_banner.php');
if (!$chat)
{
	include_once (api_get_path(LIBRARY_PATH)."online.inc.php");
	echo $accept;
	$chatcall = chatcall();
	if ($chatcall)
	{
		Display :: display_normal_message($chatcall);
	}
}

/*
-----------------------------------------------------------------------------
	Navigation menu section
-----------------------------------------------------------------------------
*/
if(api_get_setting('show_navigation_menu') != 'false' && api_get_setting('show_navigation_menu') != 'icons')
{
	Display::show_course_navigation_menu($_GET['isHidden']);
	if (isset($_cid) )
	{
		echo '<div id="menuButton">';
 		echo $output_string_menu;
 		echo '</div>';
		if(isset($_SESSION['hideMenu']))
		{
			if($_SESSION['hideMenu'] =="shown")
			{
 				if (isset($_cid) )
               	{
					echo '<div id="centerwrap"> <!-- start of #centerwrap -->';
					echo '<div id="center"> <!-- start of #center -->';
				}
			}
 		}
 		else
 		{
			if (isset($_cid) )
			{
				echo '<div id="centerwrap"> <!-- start of #centerwrap -->';
				echo '<div id="center"> <!-- start of #center -->';
			}
 		}
 	}
}

?>
<!--   Begin Of script Output   -->