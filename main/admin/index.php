<?php
/* For licensing terms, see /license.txt */

/**
 *	Index page of the admin tools
 *	@package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin', 'tracking');

// Resetting the course id.
$cidReset = true;

// Including some necessary chamilo files.
require_once '../inc/global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'admin/statistics/statistics.lib.php';
require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions.
api_protect_admin_script(true);

$nameTools = get_lang('PlatformAdmin');
$htmlHeadXtra[] = api_get_jquery_ui_js();

// Displaying the header
$message = '';

if (api_is_platform_admin()) {
    if (is_dir(api_get_path(SYS_CODE_PATH).'install/') && is_readable(api_get_path(SYS_CODE_PATH).'install/index.php')) {
        $message = Display::return_message(get_lang('InstallDirAccessibleSecurityThreat'),'warning');        
    }
    /* ACTION HANDLING */
    if (!empty($_POST['Register'])) {
        register_site();
        $message = Display :: return_message(get_lang('VersionCheckEnabled'),'confirmation');
    }
    $keyword_url = Security::remove_XSS((empty($_GET['keyword']) ? '' : $_GET['keyword']));
}

if (isset($_GET['msg']) && isset($_GET['type'])) {
	if (in_array($_GET['msg'], array('ArchiveDirCleanupSucceeded', 'ArchiveDirCleanupFailed')))
	switch($_GET['type']) {		
		case 'error':
			$message = Display::return_message(get_lang($_GET['msg']), 'error');
			break;
		case 'confirmation':			
			$message = Display::return_message(get_lang($_GET['msg']), 'confirm');
	}	
}

$blocks = array();

/* Users */

$blocks['users']['icon']  = Display::return_icon('members.gif', get_lang('Users'));
$blocks['users']['label'] = api_ucfirst(get_lang('Users'));

if (api_is_platform_admin()) {	
	$search_form = ' <form method="get" action="user_list.php">
						<input type="text" name="keyword" value="">
						<button class="search" type="submit">'.get_lang('Search').'</button>
            		</form>';
	$blocks['users']['search_form'] = $search_form;	
	$items = array(
		array('url'=>'user_list.php', 	'label' => get_lang('UserList')),
		array('url'=>'user_add.php', 	'label' => get_lang('AddUsers')),
		array('url'=>'user_export.php', 'label' => get_lang('ExportUserListXMLCSV')),
		array('url'=>'user_import.php', 'label' => get_lang('ImportUserListXMLCSV')),	
	);
	
	if (api_get_setting('allow_social_tool') == 'true') {
		$items[] = array('url'=>'group_add.php', 	'label' => get_lang('AddGroups'));
		$items[] = array('url'=>'group_list.php', 	'label' => get_lang('GroupList'));
	}
	if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
		$items[] = array('url'=>'ldap_users_list.php', 	'label' => get_lang('ImportLDAPUsersIntoPlatform'));
	}
	$items[] = array('url'=>'user_fields.php', 	'label' => get_lang('ManageUserFields'));		
} else {
 	$items = array(
 		array('url'=>'user_list.php', 	'label' => get_lang('UserList')),
 		array('url'=>'../mySpace/user_add.php', 	'label' => get_lang('AddUsers')), 	
 		array('url'=>'user_import.php', 'label' => get_lang('ImportUserListXMLCSV')),
 	);
}
$blocks['users']['items'] = $items;

if (api_is_platform_admin()) {
	/* Courses */
	$blocks['courses']['icon']  = Display::return_icon('course.gif', get_lang('Courses'));
	$blocks['courses']['label'] = api_ucfirst(get_lang('Courses'));
	
	$search_form = ' <form method="get" action="course_list.php">
							<input type="text" name="keyword" value="">
							<button class="search" type="submit">'.get_lang('Search').'</button>
	            		</form>';
	$blocks['courses']['search_form'] = $search_form;
	
	$items = array();	
	$items[] = array('url'=>'course_list.php', 	'label' => get_lang('CourseList'));
	
	if (api_get_setting('course_validation') != 'true') {
		$items[] = array('url'=>'course_add.php', 	'label' => get_lang('AddCourse'));
	} else {
		$items[] = array('url'=>'course_request_review.php', 	'label' => get_lang('ReviewCourseRequests'));
		$items[] = array('url'=>'course_request_accepted.php', 	'label' => get_lang('ReviewCourseRequests'));
		$items[] = array('url'=>'course_request_rejected.php', 	'label' => get_lang('ReviewCourseRequests'));
	}
	
	$items[] = array('url'=>'course_export.php', 			'label' => get_lang('ExportCourses'));
	$items[] = array('url'=>'course_import.php', 			'label' => get_lang('ImportCourses'));
	$items[] = array('url'=>'course_category.php', 			'label' => get_lang('AdminCategories'));
	$items[] = array('url'=>'subscribe_user2course.php', 	'label' => get_lang('AddUsersToACourse'));
	$items[] = array('url'=>'course_user_import.php', 		'label' => get_lang('ImportUsersToACourse'));
    
    
    

    if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) { 
    	$items[] = array('url'=>'ldap_import_students.php', 	'label' => get_lang('ImportLDAPUsersIntoCourse'));
    }
    $blocks['courses']['items'] = $items;
    
    /* Platform */ 
    
    $blocks['platform']['icon']  = Display::return_icon('platform.png', get_lang('Platform'), array(), 32);
    $blocks['platform']['label'] = api_ucfirst(get_lang('Platform'));
    
    $items = array();
    $items[] = array('url'=>'settings.php', 				'label' => get_lang('DokeosConfigSettings'));
    $items[] = array('url'=>'system_announcements.php', 	'label' => get_lang('SystemAnnouncements'));
    $items[] = array('url'=>api_get_path(WEB_CODE_PATH).'calendar/agenda_js.php?type=admin', 'label' => get_lang('GlobalAgenda'));
    $items[] = array('url'=>'configure_homepage.php', 		'label' => get_lang('ConfigureHomePage'));
    $items[] = array('url'=>'configure_inscription.php', 	'label' => get_lang('ConfigureInscription'));
    $items[] = array('url'=>'statistics/index.php', 		'label' => get_lang('Statistics'));
    
    if (!empty($_configuration['multiple_access_urls'])) {
		if (api_is_global_platform_admin()) {
            	$items[] = array('url'=>'access_urls.php', 	'label' => get_lang('ConfigureMultipleAccessURLs'));                
            }
    }
    
    if (api_get_setting('allow_reservation') == 'true') {
    	$items[] = array('url'=>'../reservation/m_category.php', 	'label' => get_lang('BookingSystem'));            
	}
	if (api_get_setting('allow_terms_conditions') == 'true') {
    	$items[] = array('url'=>'legal_add.php', 	'label' => get_lang('TermsAndConditions'));
	}    
	$blocks['platform']['items'] = $items;
}

/* Sessions */

if (api_get_setting('use_session_mode') == 'true') {
	
	$blocks['sessions']['icon']  = Display::return_icon('session.png', get_lang('Sessions'), array(), 22);
	$blocks['sessions']['label'] = api_ucfirst(get_lang('Sessions'));
	
	$search_form = ' <form method="POST" action="session_list.php">
								<input type="text" name="keyword" value="">
								<button class="search" type="submit">'.get_lang('Search').'</button>
		            		</form>';
	$blocks['sessions']['search_form'] = $search_form;	
	$items = array();
	$items[] = array('url'=>'session_list.php', 	'label' => get_lang('ListSession'));
	$items[] = array('url'=>'session_add.php', 	'label' => get_lang('AddSession'));
	$items[] = array('url'=>'session_category_list.php', 	'label' => get_lang('ListSessionCategory'));
	$items[] = array('url'=>'session_import.php', 	'label' => get_lang('ImportSessionListXMLCSV'));
	if (isset($extAuthSource) && isset($extAuthSource['ldap']) && count($extAuthSource['ldap']) > 0) {
		$items[] = array('url'=>'ldap_import_students_to_session.php', 	'label' => get_lang('ImportLDAPUsersIntoSession'));
	}
	$items[] = array('url'=>'session_export.php', 	'label' => get_lang('ExportSessionListXMLCSV'));
	$items[] = array('url'=>'../coursecopy/copy_course_session.php', 	'label' => get_lang('CopyFromCourseInSessionToAnotherSession'));
	$items[] = array('url'=>'session_list.php', 	'label' => get_lang('ListSession'));
	
    if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) { // option only visible in development mode. Enable through code if required 
    	$items[] = array('url'=>'user_move_stats.php', 	'label' => get_lang('MoveUserStats'));
    }            
    $items[] = array('url'=>'career_dashboard.php', 	'label' => get_lang('CareersAndPromotions'));
    $items[] = array('url'=>'usergroups.php', 	'label' => get_lang('Classes'));
    
    $blocks['sessions']['items'] = $items;

} elseif (api_is_platform_admin()) {

	$blocks['classes']['items'] = $items;
	
	$blocks['classes']['icon']  = Display::return_icon('group.gif', get_lang('AdminClasses'));
	$blocks['classes']['label'] = api_ucfirst(get_lang('AdminClasses'));
	
	$search_form = ' <form method="POST" action="class_list.php">
									<input type="text" name="keyword" value="">
									<button class="search" type="submit">.'.get_lang('Search').'</button>
			            		</form>';
	$blocks['classes']['search_form'] = $search_form;
	$items = array();
	$items[] = array('url'=>'class_list.php', 	'label' => get_lang('ClassList'));
	$items[] = array('url'=>'class_add.php', 	'label' => get_lang('AddClasses'));
	$items[] = array('url'=>'class_import.php', 	'label' => get_lang('ImportClassListCSV'));
	$items[] = array('url'=>'class_user_import.php', 	'label' => get_lang('AddUsersToAClass'));
	$items[] = array('url'=>'subscribe_class2course.php', 	'label' => get_lang('AddClassesToACourse'));
	
	$blocks['classes']['items'] = $items;
}

/* Settings */
if (api_is_platform_admin()) {	
	
	$blocks['settings']['icon']  = Display::return_icon('settings.png', get_lang('System'));
	$blocks['settings']['label'] = api_ucfirst(get_lang('System'));
	
	$items = array();
	$items[] = array('url'=>'special_exports.php', 	'label' => get_lang('SpecialExports'));
	if (!empty($_configuration['db_admin_path'])) {
		$items[] = array('url'=>$_configuration['db_admin_path'], 	'label' => get_lang('AdminDatabases').' ('.get_lang('DBManagementOnlyForServerAdmin').') ');
	}
	$items[] = array('url'=>'system_status.php', 	'label' => get_lang('SystemStatus'));
	if (is_dir(api_get_path(SYS_TEST_PATH).'datafiller/')) {
		$items[] = array('url'=>'filler.php', 	'label' => get_lang('DataFiller'));
	}
	if (api_is_global_platform_admin()) {
		$items[] = array('url'=>'archive_cleanup.php', 	'label' => get_lang('ArchiveDirCleanup'));
	}	
	$blocks['settings']['items'] = $items;

	/* Extensions */
	
	$blocks['extensions']['icon']  = Display::return_icon('visio_meeting.gif', get_lang('ConfigureExtensions'));
	$blocks['extensions']['label'] = api_ucfirst(get_lang('ConfigureExtensions'));
	
	$items = array();
	$items[] = array('url'=>'configure_extensions.php?display=visio', 	'label' => get_lang('Visioconf'));
	$items[] = array('url'=>'configure_extensions.php?display=ppt2lp', 	'label' => get_lang('Ppt2lp'));
	//$items[] = array('url'=>'configure_extensions.php?display=ephorus', 	'label' => get_lang('EphorusPlagiarismPrevention'));
	$items[] = array('url'=>'configure_extensions.php?display=search', 	'label' => get_lang('SearchEngine'));
	$items[] = array('url'=>'configure_extensions.php?display=serverstats', 	'label' => get_lang('ServerStatistics'));
	$items[] = array('url'=>'configure_extensions.php?display=bandwidthstats', 	'label' => get_lang('BandWidthStatistics'));	
	$blocks['extensions']['items'] = $items;	
    
    
    //Skills
    
    $blocks['skills']['icon']  = Display::return_icon('logo.gif', get_lang('Skills'));
    $blocks['skills']['label'] = get_lang('Skills');
    
    $items = array();
    $items[] = array('url'=>'skills.php',           'label' => get_lang('SkillsTree'));
    $items[] = array('url'=>'skills_profile.php',   'label' => get_lang('SkillsProfile'));
    $items[] = array('url'=>'skills_gradebook.php', 'label' => get_lang('SkillsAndGradebooks'));   
    
    $blocks['skills']['items'] = $items;
    

	
	/* Chamilo.org */
	
	$blocks['chamilo']['icon']  = Display::return_icon('logo.gif', 'Chamilo.org');
	$blocks['chamilo']['label'] = 'Chamilo.org';
	
	$items = array();
	$items[] = array('url'=>'http://www.chamilo.org/', 	'label' => get_lang('ChamiloHomepage'));
	$items[] = array('url'=>'http://www.chamilo.org/forum', 	'label' => get_lang('ChamiloForum'));
	
	$items[] = array('url'=>'../../documentation/installation_guide.html', 	'label' => get_lang('InstallationGuide'));
	$items[] = array('url'=>'../../documentation/changelog.html', 	'label' => get_lang('ChangesInLastVersion'));
	$items[] = array('url'=>'../../documentation/credits.html', 	'label' => get_lang('ContributorsList'));
	$items[] = array('url'=>'../../documentation/security.html', 	'label' => get_lang('SecurityGuide'));
	$items[] = array('url'=>'../../documentation/optimization.html', 	'label' => get_lang('OptimizationGuide'));
	$items[] = array('url'=>'http://www.chamilo.org/extensions', 	'label' => get_lang('ChamiloExtensions'));	
	
	$blocks['chamilo']['items'] = $items;    
	
	// Try to display a maximum before we check the chamilo version and all that.
	//session_write_close(); //close session to avoid blocking concurrent access
	//flush(); //send data to client as much as allowed by the web server
	//ob_flush();
	$blocks['chamilo']['extra'] = '<br />'.get_lang('VersionCheck').': '.version_check().'';	
}

$tpl = new Template();
$tpl->assign('blocks', $blocks);
$admin_template = $tpl->get_template('admin/settings_index.tpl');
$content = $tpl->fetch($admin_template);
$tpl->assign('content', $content);
$tpl->assign('message', $message);
$tpl->display_one_col_template();

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
    $row = Database::fetch_array($result, 'ASSOC');

    // The site has not been registered yet.
    //if (api_get_setting('registered') == 'false')

    $return = '';
    if ($row['selected_value'] == 'false') {
        $return .= '<form action="'.api_get_self().'" id="VersionCheck" name="VersionCheck" method="post">';
        $return .= get_lang('VersionCheckExplanation');
        $return .= '<input type="checkbox" name="donotlistcampus" value="1" id="checkbox" />'.get_lang('HideCampusFromPublicDokeosPlatformsList');
        $return .= '<button type="submit" class="save" name="Register" value="'.get_lang('EnableVersionCheck').'" id="register" />'.get_lang('EnableVersionCheck').'</button>';
        $return .= '</form>';
    } else {
        // The site has been registered already but is seriously out of date (registration date + 15552000 seconds).
        /*
        if ((api_get_setting('registered') + 15552000) > mktime()) {
            $return = 'It has been a long time since about your campus has been updated on chamilo.org';
            $return .= '<form action="'.api_get_self().'" id="VersionCheck" name="VersionCheck" method="post">';
            $return .= '<input type="submit" name="Register" value="Enable Version Check" id="register" />';
            $return .= '</form>';
        } else {
        */
        $return = 'site registered. ';
        $return .= check_system_version();
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
function register_site() {
    $tbl_settings = Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    $sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='registered'";
    $result = Database::query($sql);

    if ($_POST['donotlistcampus']) {
        $sql = "UPDATE $tbl_settings SET selected_value='true' WHERE variable='donotlistcampus'";
        $result = Database::query($sql);
    }

    // Reload the settings.
}


/**
 * Check if the current installation is up to date
 * The code is borrowed from phpBB and slighlty modified
 * @author The phpBB Group <support@phpbb.com> (the code)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University (the modifications)
 * @copyright (C) 2001 The phpBB Group
 * @return language string with some layout (color)
 */
function check_system_version() {
    global $_configuration;
    $system_version = trim($_configuration['system_version']); // the chamilo version of your installation

    if (ini_get('allow_url_fopen') == 1) {
        // The number of courses
        $number_of_courses = statistics::count_courses();

        // The number of users
        $number_of_users = statistics::count_users();

        $version_url = 'http://version.chamilo.org/version.php?url='.urlencode(api_get_path(WEB_PATH)).'&campus='.urlencode(api_get_setting('siteName')).'&contact='.urlencode(api_get_setting('emailAdministrator')).'&version='.urlencode($system_version).'&numberofcourses='.urlencode($number_of_courses).'&numberofusers='.urlencode($number_of_users).'&donotlistcampus='.api_get_setting('donotlistcampus').'&organisation='.urlencode(api_get_setting('Institution')).'&language='.api_get_setting('platformLanguage').'&adminname='.urlencode(api_get_setting('administratorName').' '.api_get_setting('administratorSurname'));
        $handle = @fopen($version_url, 'r');
        if ($handle !== false) {
            $version_info = trim(@fread($handle, 1024));

            if ($system_version != $version_info) {
                $output = '<br /><span style="color:red">' . get_lang('YourVersionNotUpToDate') . '. '.get_lang('LatestVersionIs').' <b>Chamilo '.$version_info.'</b>. '.get_lang('YourVersionIs').' <b>Chamilo '.$system_version. '</b>. '.str_replace('http://www.chamilo.org', '<a href="http://www.chamilo.org">http://www.chamilo.org</a>', get_lang('PleaseVisitDokeos')).'</span>';
            } else {
                $output = '<br /><span style="color:green">'.get_lang('VersionUpToDate').': Chamilo '.$version_info.'</span>';
            }
        } else {
            $output = '<span style="color:red">' . get_lang('ImpossibleToContactVersionServerPleaseTryAgain') . '</span>';
        }
    } else {
        $output = '<span style="color:red">' . get_lang('AllowurlfopenIsSetToOff') . '</span>';
    }
    return $output;
}
