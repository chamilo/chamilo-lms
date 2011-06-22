<?php // $Id: index.php 22269 2009-07-21 15:06:15Z juliomontoya $
/* For licensing terms, see /license.txt */
/**
*	Index of the admin tools
*
*	@package chamilo.admin
*/
// name of the language file that needs to be included <br />
$language_file=array('admin','tracking');

// resetting the course id
$cidReset=true;

// including some necessary chamilo files
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'security.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php';
require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);
$nameTools = get_lang('PlatformAdmin');

// setting breadcrumbs
//$interbreadcrumb[] = array('url' => 'index.php', 'name' => $nameTools);

// setting the name of the tool
$tool_name=get_lang('PlatformAdmin');

// Displaying the header
Display::display_header($nameTools);


if(api_is_platform_admin()) {
	if(is_dir(api_get_path(SYS_CODE_PATH).'install/') && is_readable(api_get_path(SYS_CODE_PATH).'install/index.php'))
	{
		Display::display_normal_message(get_lang('InstallDirAccessibleSecurityThreat'));
	}
	/*
				ACTION HANDLING
		*/
	if (!empty($_POST['Register']))
	{
		register_site();
		Display :: display_confirmation_message(get_lang('VersionCheckEnabled'));
	}

	/*
			MAIN SECTION
	*/
	$keyword_url = Security::remove_XSS((empty($_GET['keyword'])?'':$_GET['keyword']));
}

if (api_is_platform_admin()) {
	?>
		<div class="admin_section">
	<h4><?php Display::display_icon('members.gif', get_lang('Users')); ?> <?php echo api_ucfirst(get_lang('Users')); ?></h4>

		<div style="list-style-type:none"><form method="get" action="user_list.php">
			<input type="text" name="keyword" value="<?php echo $keyword_url; ?>"/>
			<button class="search" type="submit"> <?php echo get_lang('Search');?>
			</button>
			</form>
		</div>
	<ul>
		<!-- <li><a href="user_list.php?search=advanced"><?php echo api_ucfirst(get_lang('AdvancedSearch')); ?></a></li> -->
		<li><a href="user_list.php">	<?php echo get_lang('UserList') ?></a></li>
		<li><a href="user_add.php">		<?php echo get_lang('AddUsers') ?></a></li>
		<li><a href="user_export.php">	<?php echo get_lang('ExportUserListXMLCSV') ?></a></li>
		<li><a href="user_import.php">	<?php echo get_lang('ImportUserListXMLCSV') ?></a></li>
		<?php if (api_get_setting('allow_social_tool')=='true') { ?>
			<li><a href="group_add.php">	<?php echo get_lang('AddGroups') ?></a></li>
			<li><a href="group_list.php">	<?php echo get_lang('GroupList') ?></a></li>
		<?php
		}
		if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
			?>
			<!-- dynamic ldap code -->
			  <li><a href="ldap_users_list.php"><?php echo get_lang('ImportLDAPUsersIntoPlatform');?></a></li>
			<!-- dynamic ldap code -->
			<?php
			}
		?>
		<li><a href="user_fields.php">	<?php echo get_lang('ManageUserFields'); ?></a></li>
		</ul>
		</div>
<?php
} else {
	?>
	<div class="admin_section">
	<h4><?php Display::display_icon('members.gif', get_lang('Users')); ?> <?php echo api_ucfirst(get_lang('Users')); ?></h4>
	<ul>
		<li><a href="user_list.php">	<?php echo get_lang('UserList') ?></a></li>
		<li><a href="../mySpace/user_add.php"><?php echo get_lang('AddUsers') ?></a></li>
		<li><a href="user_import.php">	<?php echo get_lang('ImportUserListXMLCSV') ?></a></li>
	</ul>
	</div>
<?php
}

//Advance search link moved
/* <li><a href="course_list.php?search=advanced"><?php echo api_ucfirst(get_lang('AdvancedSearch')); ?></a></li> */

if(api_is_platform_admin()) {
?>
	<div class="admin_section">
		<h4><?php Display::display_icon('course.gif', get_lang('Courses')); ?> <?php echo api_ucfirst(get_lang('Courses')); ?></h4>
		<div style="list-style-type:none">
			<form method="get" action="course_list.php">
				<input type="text" name="keyword" value="<?php echo $keyword_url; ?>"/>
				<button class="search" type="submit"> <?php echo get_lang('Search');?></button>
			</form>
		</div>
	<ul>
		<li><a href="course_list.php"><?php echo get_lang('CourseList') ?></a></li>
		<li><a href="course_add.php"><?php echo get_lang('AddCourse') ?></a></li>
		<li><a href="course_export.php"><?php echo get_lang('ExportCourses'); ?></a></li>
		<li><a href="course_import.php"><?php echo get_lang('ImportCourses'); ?></a></li>
		<!-- <li><a href="course_virtual.php"><?php //echo get_lang('AdminManageVirtualCourses') ?></a></li> -->
		<li><a href="course_category.php"><?php echo get_lang('AdminCategories'); ?></a></li>
		<li><a href="subscribe_user2course.php"><?php echo get_lang('AddUsersToACourse'); ?></a></li>
		<li><a href="course_user_import.php"><?php echo get_lang('ImportUsersToACourse'); ?></a></li>
		<?php if (api_get_setting('search_enabled')=='true') { ?>
		  <li><a href="specific_fields.php"><?php echo get_lang('SpecificSearchFields'); ?></a></li>
		<?php } ?>
		<?php
			if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
			?>
			<!-- dynamic ldap code -->
			<li><a href="ldap_import_students.php"><?php echo get_lang('ImportLDAPUsersIntoCourse');?></a></li>
			<!-- dynamic ldap code -->
			<?php
			}
		?>
	</ul>
	</div>

	<div class="admin_section">
	 <h4><?php Display::display_icon('settings.gif', get_lang('Platform')); ?> <?php echo api_ucfirst(get_lang('Platform')); ?></h4>
	 <ul>
	  <li><a href="settings.php"><?php echo get_lang('DokeosConfigSettings') ?></a></li>
	  <li><a href="special_exports.php"><?php echo get_lang('SpecialExports') ?></a></li>
	  <li><a href="system_announcements.php"><?php echo get_lang('SystemAnnouncements') ?></a></li>
	  <li><a href="languages.php"><?php echo get_lang('Languages'); ?></a></li>
	  <li><a href="configure_homepage.php"><?php echo get_lang('ConfigureHomePage'); ?></a></li>
	  <li><a href="configure_inscription.php"><?php echo get_lang('ConfigureInscription'); ?></a></li>
	  <li><a href="statistics/index.php"><?php echo get_lang('Statistics'); ?> </a></li>
	  <li><a href="calendar.php"><?php echo get_lang('GlobalAgenda'); ?> </a></li>
	  <?php if(!empty($phpMyAdminPath)) { ?>
	  <li><a href="<?php echo $phpMyAdminPath; ?>" target="_blank"><?php echo get_lang("AdminDatabases"); ?></a><br />(<?php echo get_lang("DBManagementOnlyForServerAdmin"); ?>)</li>
	  <?php } ?>
	  <?php
		if(!empty($_configuration['multiple_access_urls'])) {
			if (api_is_global_platform_admin()) {
	    		echo '<li><a href="access_urls.php">'.get_lang('ConfigureMultipleAccessURLs').'</a></li>';
			}
	  }

	  if (api_get_setting('allow_reservation')=='true') {
		  	echo '<li><a href="../reservation/m_category.php">'.get_lang('BookingSystem').'</a></li>';
	  }

  	  if (api_get_setting('allow_terms_conditions')=='true') {
		  	echo '<li><a href="legal_add.php">'.get_lang('TermsAndConditions').'</a></li>';
	  }

	  //@todo Translations needed in order to see a better explanation of issues
	  echo '<li><a href="system_status.php">'.get_lang('SystemStatus').'</a></li>';

	  if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
      //do not show on production portals, where the tests directory doesn't exist
	      echo '<li><a href="filler.php">'.get_lang('DataFiller').'</a></li>';
      }
	  ?>
    <?php if (api_get_setting('activate_send_event_by_mail') === 'true'): ?>
	    <li><a href="event_type.php"><?php print  get_lang('events_title'); ?></a></li>
    <?php endif ?>
	 </ul>
	</div>

	<?php
}

if(api_get_setting('use_session_mode')=='true') {
?>

<div class="admin_section">
 <h4><?php Display::display_icon('blackboard_blue.png', get_lang('Sessions'), array('width'=>'22px')); ?> <?php echo get_lang('Sessions') ?></h4>
 <div style="list-style-type:none"><form method="POST" action="session_list.php">
	<input type="text" name="keyword_name" value="<?php echo $keyword_url; ?>"/>
	<button class="search" type="submit"> <?php echo get_lang('Search');?>
			</button>
	</form>
</div>

 <ul>
  <!--  <li><a href="session_list.php?search=advanced"><?php echo api_ucfirst(get_lang('AdvancedSearch')); ?></a></li> -->
  <li><a href="session_list.php"><?php echo get_lang('ListSession') ?></a></li>
  <li><a href="session_category_list.php"><?php echo get_lang('ListSessionCategory') ?></a></li>
  <li><a href="session_add.php"><?php echo get_lang('AddSession') ?></a></li>
  <li><a href="session_import.php"><?php echo get_lang('ImportSessionListXMLCSV') ?></a></li>
  <?php
		if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
		?>
		<!-- dynamic ldap code -->
		 <li><a href="ldap_import_students_to_session.php"><?php echo get_lang('ImportLDAPUsersIntoSession');?></a></li>
		<!-- dynamic ldap code -->
		<?php
		}
	?>
  <li><a href="session_export.php"><?php echo get_lang('ExportSessionListXMLCSV') ?></a></li>
  <li><a href="../coursecopy/copy_course_session.php"><?php echo get_lang('CopyFromCourseInSessionToAnotherSession') ?></a></li>
  </ul>
  </div>

<?php
} else if(api_is_platform_admin()) {
?>

<div class="admin_section">
<h4><?php Display::display_icon('group.gif', get_lang('AdminClasses')); ?> <?php echo api_ucfirst(get_lang('AdminClasses')); ?></h4>
<div style="list-style-type:none"><form method="get" action="class_list.php">
	<input type="text" name="keyword" value="<?php echo $keyword_url; ?>"/>
	<input class="search" type="submit" value="<?php echo get_lang('Search'); ?>"/>
	</form>
</div>
<ul>
<li><a href="class_list.php"><?php echo get_lang('ClassList'); ?></a></li>
<li><a href="class_add.php"><?php echo get_lang('AddClasses'); ?></a></li>
<li><a href="class_import.php"><?php echo get_lang('ImportClassListCSV'); ?></a></li>
<li><a href="class_user_import.php"><?php echo get_lang('AddUsersToAClass'); ?> CSV</a></li>
<li><a href="subscribe_class2course.php"><?php echo get_lang('AddClassesToACourse'); ?></a></li>
</ul>
<br />
<br />
</div>
<?php
}

if (api_is_platform_admin()) {
?>

<div class="admin_section">
 <h4><?php Display::display_icon('visio_meeting.gif', get_lang('ConfigureExtensions')); ?> <?php echo api_ucfirst(get_lang('ConfigureExtensions')); ?></h4>
 <ul>
  <li><a href="configure_extensions.php?display=visio"><?php echo get_lang('Visioconf'); ?></a></li>
  <li><a href="configure_extensions.php?display=ppt2lp"><?php echo get_lang('Ppt2lp'); ?></a></li>
  <?php
  /* <li><a href="configure_extensions.php?display=ephorus"><?php echo get_lang('EphorusPlagiarismPrevention'); ?></a></li> */
  ?>
  <li><a href="configure_extensions.php?display=search"><?php echo get_lang('SearchEngine'); ?></a></li>
  <li><a href="configure_extensions.php?display=serverstats"><?php echo get_lang('ServerStatistics'); ?></a></li>
  <li><a href="configure_extensions.php?display=bandwidthstats"><?php echo get_lang('BandWidthStatistics'); ?></a></li>
  </ul>
</div>
<?php
	/*if(isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap'])>0){
	?>
	<!-- dynamic ldap code -->
	<div class="admin_section">
	 <h4><?php Display::display_icon('members.gif', 'LDAP'); ?> LDAP</h4>
	 <ul>
	  <li><a href="ldap_users_list.php"><?php echo get_lang('ImportLDAPUsersIntoPlatform');?></a></li>
	  <li><a href="ldap_import_students.php"><?php echo get_lang('ImportLDAPUsersIntoCourse');?></a></li>
	  <li><a href="ldap_import_students_to_session.php"><?php echo get_lang('ImportLDAPUsersIntoSession');?></a></li>
	  <!--li><a href="ldap_users_synchro.php"><?php echo get_lang('LDAPSynchroImportUsersAndStepsInSessions');?></a></li-->
	 </ul>
	</div>
	<!-- dynamic ldap code -->
	<?php
	}*/
?>

<div class="admin_section">
 <h4><?php Display::display_icon('logo.gif', 'Chamilo'); ?> Chamilo.org</h4>
 <ul>
  <li><a href="http://www.chamilo.org/" target="_blank"><?php echo get_lang('ChamiloHomepage'); ?></a></li>
  <li><a href="http://www.chamilo.org/forum" target="_blank"><?php echo get_lang('ChamiloForum'); ?></a></li>
  <li><a href="http://www.chamilo.org/extensions" target="_blank"><?php echo get_lang('ChamiloExtensions'); ?></a></li>

  <?php
  //try to display a maximum before we check the chamilo version and all that
  	//session_write_close(); //close session to avoid blocking concurrent access
	flush(); //send data to client as much as allowed by the web server
	//ob_flush();
	echo '<br />'.get_lang('VersionCheck').': '.version_check().'';
  ?>
 </ul>
</div>
<?php
}

/**
 * Displays either the text for the registration or the message that the installation is (not) up to date
 *
 * @return string html code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version august 2006
 * @todo have a 6monthly re-registration
 */
function version_check() {
	$tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$sql = 'SELECT selected_value FROM  '.$tbl_settings.' WHERE variable="registered" ';
	$result = Database::query($sql);
	$row=Database::fetch_array($result,'ASSOC');

	// The site has not been registered yet
	//if (api_get_setting('registered')=='false')

	$return = '';
	if ($row['selected_value']=='false')
	{
		$return .= '<form action="'.api_get_self().'" id="VersionCheck" name="VersionCheck" method="post">';
		$return .= get_lang('VersionCheckExplanation');
		$return .= '<input type="checkbox" name="donotlistcampus" value="1" id="checkbox" />'.get_lang('HideCampusFromPublicDokeosPlatformsList');
		$return .= '<button type="submit" class="save" name="Register" value="'.get_lang('EnableVersionCheck').'" id="register" />'.get_lang('EnableVersionCheck').'</button>';
		$return .= '</form>';
	} else {
		// The site has been registered already but is seriously out of date (registration date + 15552000 seconds)
		/*
		if ((api_get_setting('registered') + 15552000) > mktime())
		{
			$return = 'It has been a long time since about your campus has been updated on chamilo.org';
			$return .= '<form action="'.api_get_self().'" id="VersionCheck" name="VersionCheck" method="post">';
			$return .= '<input type="submit" name="Register" value="Enable Version Check" id="register" />';
			$return .= '</form>';
		}
		else
		{
		*/
		$return = 'site registered. ';
		$return .= check_system_version2();
		//}
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
	// Database Table Definitions
	$tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

	// the SQL statment
	$sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='registered'";
	$result = Database::query($sql);

	//
	if ($_POST['donotlistcampus'])
	{
		$sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='donotlistcampus'";
		$result = Database::query($sql);
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
function check_system_version2()
{
	global $_configuration;
	$system_version = trim($_configuration['system_version']); // the chamilo version of your installation

	if (ini_get('allow_url_fopen')==1)
	{
		// the number of courses
	    $number_of_courses = statistics::count_courses();

		// the number of users
	   	$number_of_users = statistics::count_users();

		$version_url= 'http://version.chamilo.org/version.php?url='.urlencode(api_get_path(WEB_PATH)).'&campus='.urlencode(api_get_setting('siteName')).'&contact='.urlencode(api_get_setting('emailAdministrator')).'&version='.urlencode($system_version).'&numberofcourses='.urlencode($number_of_courses).'&numberofusers='.urlencode($number_of_users).'&donotlistcampus='.api_get_setting('donotlistcampus').'&organisation='.urlencode(api_get_setting('Institution')).'&adminname='.urlencode(api_get_setting('administratorName').' '.api_get_setting('administratorSurname'));

		$handle=@fopen($version_url,'r');
		if ($handle !== false) {
			$version_info=trim(@fread($handle, 1024));

			if ($system_version <> $version_info) {
				$output='<br /><span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Chamilo '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Chamilo '.$system_version. '</b>. '.str_replace('http://www.chamilo.org','<a href="http://www.chamilo.org">http://www.chamilo.org</a>',get_lang('PleaseVisitDokeos')).'</span>';
			} else {
				$output = '<br /><span style="color:green">'.get_lang('VersionUpToDate').': Chamilo '.$version_info.'</span>';
			}
		} else {
            $output = '<span style="color:red">' . get_lang('ImpossibleToContactVersionServerPleaseTryAgain') . '</span>';
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
function check_system_version()
{
	global $_configuration; // the chamilo version of your installation
	$system_version = $_configuration['system_version'];

	if ($fsock = @fsockopen('www.chamilo.org', 80, $errno, $errstr))
	{
		@fputs($fsock, "GET /version.php HTTP/1.1\r\n");
		@fputs($fsock, "HOST: www.chamilo.org\r\n");
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

		if (trim($system_version) <> trim($version_info)) {
			$output='<span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Chamilo '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Dokeos '.$system_version. '</b>. '.str_replace('http://www.chamilo.org','<a href="http://www.chamilo.org">http://www.chamilo.org</a>',get_lang('PleaseVisitDokeos')).'</span>';
		} else {
			$output = '<span style="color:green">'.get_lang('VersionUpToDate').': Chamilo '.$version_info.'</span>';
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
		FOOTER
*/
Display::display_footer();
?>
