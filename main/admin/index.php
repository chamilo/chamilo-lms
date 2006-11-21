<?php // $Id: index.php 10082 2006-11-21 19:08:15Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Index of the admin tools
*
*	@package dokeos.admin
==============================================================================
*/

$langFile='admin';

$cidReset=true;

include('../inc/global.inc.php');
include_once('../inc/installedVersion.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$tool_name=get_lang("PlatformAdmin");

Display::display_header();

/*
==============================================================================
		ACTION HANDLING
==============================================================================
*/
if ($_POST['Register'])
{
	register_site();
	Display :: display_confirmation_message('Version Check Enabled');
}

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/

?>

<div class="admin_section">
<h4><img src="../img/members.gif" border="0" style="vertical-align: middle;" alt="" /> <?php echo ucfirst(get_lang('Users')); ?></h4>
	<ul><li style="list-style-type:none"><form method="get" action="user_list.php">

	<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form></li>
	<li>
<a href="user_list.php?search=advanced"><?php echo get_lang('AdvancedSearch'); ?></a>
	
</li>
<li><a href="user_list.php"><?php echo get_lang('UserList') ?></a></li>
<li><a href="user_add.php"><?php echo get_lang('AddUsers') ?></a></li>
<li><a href="user_export.php"><?php echo get_lang('ExportUserListXMLCSV') ?></a></li>
<li><a href="user_import.php"><?php echo get_lang('ImportUserListXMLCSV') ?></a></li>
</ul>
</div>

<div class="admin_section">
<h4><img src="../img/course.gif" border="0" style="vertical-align: middle;" alt="" /> <?php echo ucfirst(get_lang('Courses')); ?></h4>
	<ul><li style="list-style-type:none"><form method="get" action="course_list.php">

	<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form></li>
	<li>
	<a href="course_list.php?search=advanced"><?php echo get_lang('AdvancedSearch'); ?></a>
	
</li>
<li><a href="course_list.php"><?php echo get_lang('CourseList') ?></a></li>
<li><a href="course_add.php"><?php echo get_lang('AddCourse') ?></a></li>
<li><a href="course_import.php"><?php echo get_lang('AddCourse').' CSV'; ?></a></li>
<!--<li><a href="course_virtual.php"><?php //echo get_lang('AdminManageVirtualCourses') ?></a></li>-->
<li><a href="course_category.php"><?php echo get_lang("AdminCategories"); ?></a></li>
<li><a href="subscribe_class2course.php"><?php echo get_lang('AddClassesToACourse'); ?></a></li>
<li><a href="subscribe_user2course.php"><?php echo get_lang('AddUsersToACourse'); ?></a></li>
<li><a href="course_user_import.php"><?php echo get_lang('AddUsersToACourse').' CSV'; ?></a></li>
</ul>
</div>

<div class="admin_section">
 <h4>
  <img src="../img/settings.gif" border="0" style="vertical-align: middle;" alt="" />
  <?php echo ucfirst(get_lang('Platform')); ?>
 </h4>
 <ul>
  <li><a href="settings.php"><?php echo get_lang('DokeosConfigSettings') ?></a></li>
  <li><a href="system_announcements.php"><?php echo get_lang('SystemAnnouncements') ?></a></li>
  <li><a href="languages.php"><?php echo get_lang('Languages'); ?></a></li>
  <li><a href="configure_homepage.php"><?php echo get_lang('ConfigureHomePage'); ?></a></li>
  <!-- <li><a href="configure_extensions.php"><?php echo get_lang('ConfigureExtensions'); ?></a></li> -->
  <li><a href="configure_extensions.php?display=visio"><?php echo get_lang('Visioconf'); ?></a></li>
  <li><a href="configure_extensions.php?display=ppt2lp"><?php echo get_lang('Ppt2lp'); ?></a></li>
  <li><a href="configure_extensions.php?display=search"><?php echo get_lang('SearchEngine'); ?></a></li>
  <li><a href="configure_extensions.php?display=serverstats"><?php echo get_lang('ServerStatistics'); ?></a></li>
  <li><a href="configure_extensions.php?display=bandwidthstats"><?php echo get_lang('BandWidthStatistics'); ?></a></li>
  
  <?php if(!empty($phpMyAdminPath)): ?>
  <li><a href="<?php echo $phpMyAdminPath; ?>" target="_blank"><?php echo get_lang("AdminDatabases"); ?></a><br />(<?php echo get_lang("DBManagementOnlyForServerAdmin"); ?>)</li>
  <?php endif; ?>

 </ul>
</div>


<?php
if(api_get_setting('use_session_mode')=='true')
{
?>

<div class="admin_section">
 <h4>
  <img src="../img/agenda.gif" border="0" align="absmiddle" alt="" />
  <?php echo get_lang('Sessions') ?>
 </h4>
 <ul>
 <li style="list-style-type:none"><form method="POST" action="session_list.php">
	<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form>
</li>
  <li><a href="session_list.php"><?php echo get_lang('ListSession') ?></a></li>
  <li><a href="session_add.php"><?php echo get_lang('AddSession') ?></a></li>
  <li><a href="session_import.php"><?php echo get_lang('ImportSessionListXMLCSV') ?></a></li>
  <li><a href="session_export.php"><?php echo get_lang('ExportSessionListXMLCSV') ?></a></li>
  </ul>
</div>

<?php
}
else
{
?>

<div class="admin_section">
<h4><img src="../img/group.gif" border="0" style="vertical-align: middle;" alt="" /> <?php echo ucfirst(get_lang('AdminClasses')); ?></h4>
<ul>
<li style="list-style-type:none"><form method="get" action="class_list.php">

	<input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"/>
	<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form>
</li>
<li><a href="class_list.php"><?php echo get_lang('ClassList'); ?></a></li>
<li><a href="class_add.php"><?php echo get_lang('AddClasses'); ?></a></li>
<li><a href="class_import.php"><?php echo get_lang('ImportClassListCSV'); ?></a></li>
<li><a href="class_user_import.php"><?php echo get_lang('AddUsersToAClass'); ?> CSV</a></li>
</ul>
</div>
<?php
}
?>


<div class="admin_section">
 <h4>
  <img src="../img/dokeos.gif" border="0" style="vertical-align: middle;" alt="" />
  <?php echo "dokeos.com"; ?>
 </h4>
 <ul>
  <li><a href="http://www.dokeos.com/"><?php echo get_lang('DokeosHomepage'); ?></a></li>
  <li><a href="http://www.dokeos.com/forum/"><?php echo get_lang('DokeosForum'); ?></a></li>
  <li><a href="http://www.dokeos.com/community_add_portal.php?url=<?php echo $_configuration['root_web']; ?>&amp;name=<?php echo urlencode(get_setting('siteName'));?>&amp;organisation=<?php echo urlencode(get_setting('InstitutionUrl'));?>&amp;manager=<?php echo urlencode(get_setting('administratorSurname')." ".get_setting('administratorName'));?>&amp;manageremail=<?php echo urlencode(get_setting('emailAdministrator'));?>"><?php echo get_lang('RegisterYourPortal'); ?></a></li>
  <li><a href="http://www.dokeos.com/extensions/"><?php echo get_lang('DokeosExtensions'); ?></a></li>
  <li>
  <?php
  //try to display a maximum before we check the dokeos version and all that
	flush(); //send data to client as much as allowed by the web server
	ob_flush();
	echo get_lang('VersionCheck').': '.version_check();
  ?>
  </li>
 </ul>
</div>


<?php
/**
 * Displays either the text for the registration or the message that the installation is (not) up to date
 *
 * @return string html code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version august 2006
 * @todo have a 6monthly re-registration
 */
function version_check()
{
	// The site has not been registered yet
	if (get_setting('registered')=='false' OR get_setting('registered') == false )
	{
		$return = '';
		$return .= '<form action="'.$_SERVER['PHP_SELF'].'" id="VersionCheck" name="VersionCheck" method="post">';
		$return .= get_lang('VersionCheckExplanation'); 
		$return .= '<input type="checkbox" name="donotlistcampus" value="1" id="checkbox" /> Do not display my campus in the list of Dokeos platforms';
		$return .= '<input type="submit" name="Register" value="Enable Version Check" id="register" />';
		$return .= '</form>';
	}
	else
	{
		// The site has been registered already but is seriously out of date (registration date + 15552000 seconds)
		if ((get_setting('registered') + 15552000) > mktime())
		{
			$return = 'It has been a long time since about your campus has been updated on Dokeos.com';
			$return .= '<form action="'.$_SERVER['PHP_SELF'].'" id="VersionCheck" name="VersionCheck" method="post">';
			$return .= '<input type="submit" name="Register" value="Enable Version Check" id="register" />';
			$return .= '</form>';
		}
		else
		{
			$return = 'site registered';
			$return .= check_dokeos_version2();
		}
	}

	return $return;

}

/**
 * This setting changes the registration status for the campus
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version August 2006
 *
 * @todo the $_settings should be reloaded here. => write api function for this and use this in global.inc.php also.
 */
function register_site()
{
	// the settings table
	$tbl_settings = Database :: get_main_table(MAIN_SETTINGS_CURRENT_TABLE);

	// the SQL statment
	$sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='registered'";
	$result = api_sql_query($sql);

	//
	if ($_POST['donotlistcampus'])
	{
		$sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='donotlistcampus'";
		$result = api_sql_query($sql);
	}

	// reload the settings
}

/**
* Check if the current installation is up to date
* The code is borrowed from phpBB and slighlty modified
* @author The phpBB Group <support@phpbb.com> (the code)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (the modifications)
* @copyright (C) 2001 The phpBB Group
* @return language string with some layout (color)
*/
function check_dokeos_version2()
{
	global $dokeos_version; // the dokeos version of your installation

	if (ini_get('allow_url_fopen')==1)
	{
		// the number of courses
		$sql="SELECT code FROM ".Database::get_main_table(MAIN_COURSE_TABLE);
		$result=api_sql_query($sql);
		$number_of_courses = mysql_num_rows($result);

		// the number of users
		$sql="SELECT user_id FROM ".Database::get_main_table(MAIN_USER_TABLE);
		$result=api_sql_query($sql);
		$number_of_users = mysql_num_rows($result);

		$version_url= 'http://www.dokeos.com/version.php?url='.urlencode(api_get_path(WEB_PATH)).'&campus='.urlencode(api_get_setting('siteName')).'&contact='.urlencode(get_setting('emailAdministrator')).'&version='.urlencode($dokeos_version).'&numberofcourses='.urlencode($number_of_courses).'&numberofusers='.urlencode($number_of_users).'&donotlistcampus='.get_setting('donotlistcampus');
		$handle=@fopen($version_url,'r');
		$version_info=@fread($handle, 1024);

		if ($dokeos_version<>$version_info)
		{
			$output='<span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Dokeos '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Dokeos '.$dokeos_version. '</b>. '.str_replace('http://www.dokeos.com','<a href="http://www.dokeos.com">http://www.dokeos.com</a>',get_lang('PleaseVisitDokeos')).'</span>';
		}
		else
		{
			$output = '<span style="color:green">'.get_lang('VersionUpToDate').': Dokeos '.$version_info.'</span>';
		}
	}
	else
	{
		$output = '<span style="color:red">' . get_lang('AllowurlfopenIsSetToOff') . '</span>';
	}
	return $output;
}

/**
* Check if the current installation is up to date
* The code is borrowed from phpBB and slighlty modified
* @author The phpBB Group <support@phpbb.com> (the code)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (the modifications)
* @copyright (C) 2001 The phpBB Group
* @return language string with some layout (color)
* @deprecated 	For some reason this code adds a 9 in front and a 0 at the end of what normally gets displayed by
				the http://www.dokeos.com/version.php page (instead of version.txt) . That's why I chose to use fopen which requires however
				that allow_url_open is set to true
*/
function check_dokeos_version()
{
	global $dokeos_version; // the dokeos version of your installation

	if ($fsock = @fsockopen('www.dokeos.com', 80, $errno, $errstr))
	{
		@fputs($fsock, "GET /version.php HTTP/1.1\r\n");
		@fputs($fsock, "HOST: www.dokeos.com\r\n");
		@fputs($fsock, "Connection: close\r\n\r\n");

		$get_info = false;
		while (!@feof($fsock))
		{
			if ($get_info)
			{
				$version_info .= @fread($fsock, 1024);
			}
			else
			{
				if (@fgets($fsock, 1024) == "\r\n")
				{
					$get_info = true;
				}
			}
		}
		@fclose($fsock);

		if ($dokeos_version<>$version_info)
		{
			$output='<span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Dokeos '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Dokeos '.$dokeos_version. '</b>. '.str_replace('http://www.dokeos.com','<a href="http://www.dokeos.com">http://www.dokeos.com</a>',get_lang('PleaseVisitDokeos')).'</span>';
		}
		else
		{
			$output = '<span style="color:green">'.get_lang('VersionUpToDate').': Dokeos '.$version_info.'</span>';
		}
	}
	else
	{
		if ($errstr)
		{
			$output = '<span style="color:red">' . get_lang('ConnectSocketError') . ': '. $errstr . '</span>';
		}
		else
		{
			$output = '<span>' . get_lang('SocketFunctionsDisabled') . '</span>';
		}
	}
	return $output;
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>