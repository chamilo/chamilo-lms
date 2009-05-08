<?php
/*
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Julio Montoya
	Copyright (c) Hugues Peeters
	Copyright (c) Christophe Gesche
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)
	Copyright (c) Patrick Cool
	Copyright (c) Olivier Brouckaert
	Copyright (c) Toon Van Hoecke
	Copyright (c) Denes Nagy
	Copyright (c) Isaac Flores

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium	
	Mail: info@dokeos.com
===============================================================================
*/

/*
==============================================================================
		MAIN API EXTENSIONS
==============================================================================
*/

// Including extensions to the main API works in two different ways:
if (api_get_path(LIBRARY_PATH) == '/lib/') {
	// when we are running the installer script.
	require_once 'multibyte_string_functions.lib.php';
} else {
	// when the system has been already installed, this is the usual way.
	require_once api_get_path(LIBRARY_PATH).'multibyte_string_functions.lib.php';
}

/**
==============================================================================
*	This is a code library for Dokeos.
*	It is included by default in every Dokeos file
*	(through including the global.inc.php)
*
*	@package dokeos.library
==============================================================================
*/
/*
==============================================================================
		CONSTANTS
==============================================================================
*/

//USER STATUS CONSTANTS
/** global status of a user: student */
define('STUDENT', 5);
/** global status of a user: course manager */
define('COURSEMANAGER', 1);
/** global status of a user: session admin */
define('SESSIONADMIN', 3);
/** global status of a user: human ressource manager */
define('DRH', 4);
/** global status of a user: human ressource manager */
define('ANONYMOUS', 6);

// table of status
$_status_list[STUDENT] = 'user';
$_status_list[COURSEMANAGER] = 'teacher';
$_status_list[SESSIONADMIN] = 'session_admin';
$_status_list[DRH] = 'drh';
$_status_list[ANONYMOUS] = 'anonymous';


//COURSE VISIBILITY CONSTANTS
/** only visible for course admin */
define('COURSE_VISIBILITY_CLOSED', 0);
/** only visible for users registered in the course*/
define('COURSE_VISIBILITY_REGISTERED', 1);
/** open for all registered users on the platform */
define('COURSE_VISIBILITY_OPEN_PLATFORM', 2);
/** open for the whole world */
define('COURSE_VISIBILITY_OPEN_WORLD', 3);

define('SUBSCRIBE_ALLOWED', 1);
define('SUBSCRIBE_NOT_ALLOWED', 0);
define('UNSUBSCRIBE_ALLOWED', 1);
define('UNSUBSCRIBE_NOT_ALLOWED', 0);

//CONSTANTS FOR api_get_path FUNCTION
define('WEB_PATH', 'WEB_PATH');
define('SYS_PATH', 'SYS_PATH');
define('REL_PATH', 'REL_PATH');
define('WEB_COURSE_PATH', 'WEB_COURSE_PATH');
define('SYS_COURSE_PATH', 'SYS_COURSE_PATH');
define('REL_COURSE_PATH', 'REL_COURSE_PATH');
define('REL_CODE_PATH', 'REL_CODE_PATH');
define('WEB_CODE_PATH', 'WEB_CODE_PATH');
define('SYS_CODE_PATH', 'SYS_CODE_PATH');
define('SYS_LANG_PATH', 'SYS_LANG_PATH');
define('WEB_IMG_PATH', 'WEB_IMG_PATH');
define('WEB_CSS_PATH', 'WEB_CSS_PATH');
define('GARBAGE_PATH', 'GARBAGE_PATH');
define('SYS_PLUGIN_PATH', 'SYS_PLUGIN_PATH');
define('PLUGIN_PATH', 'PLUGIN_PATH');
define('WEB_PLUGIN_PATH', 'WEB_PLUGIN_PATH');
define('SYS_ARCHIVE_PATH', 'SYS_ARCHIVE_PATH');
define('INCLUDE_PATH', 'INCLUDE_PATH');
define('LIBRARY_PATH', 'LIBRARY_PATH');
define('CONFIGURATION_PATH', 'CONFIGURATION_PATH');
define('WEB_LIBRARY_PATH','WEB_LIBRARY_PATH');

//CONSTANTS defining all tools, using the english version
define('TOOL_DOCUMENT', 'document');
define('TOOL_THUMBNAIL', 'thumbnail');
define('TOOL_HOTPOTATOES', 'hotpotatoes');
define('TOOL_CALENDAR_EVENT', 'calendar_event');
define('TOOL_LINK', 'link');
define('TOOL_COURSE_DESCRIPTION', 'course_description');
define('TOOL_SEARCH', 'search');
define('TOOL_LEARNPATH', 'learnpath');
define('TOOL_ANNOUNCEMENT', 'announcement');
define('TOOL_FORUM', 'forum');
define('TOOL_THREAD', 'thread');
define('TOOL_POST', 'post');
define('TOOL_DROPBOX', 'dropbox');
define('TOOL_QUIZ', 'quiz');
define('TOOL_USER', 'user');
define('TOOL_GROUP', 'group');
define('TOOL_BLOGS', 'blog_management'); // Smartblogs (Kevin Van Den Haute :: kevin@develop-it.be)
define('TOOL_CHAT', 'chat');
define('TOOL_CONFERENCE', 'conference');
define('TOOL_STUDENTPUBLICATION', 'student_publication');
define('TOOL_TRACKING', 'tracking');
define('TOOL_HOMEPAGE_LINK', 'homepage_link');
define('TOOL_COURSE_SETTING', 'course_setting');
define('TOOL_BACKUP', 'backup');
define('TOOL_COPY_COURSE_CONTENT', 'copy_course_content');
define('TOOL_RECYCLE_COURSE', 'recycle_course');
define('TOOL_COURSE_HOMEPAGE', 'course_homepage');
define('TOOL_COURSE_RIGHTS_OVERVIEW', 'course_rights');
define('TOOL_UPLOAD','file_upload');
define('TOOL_COURSE_MAINTENANCE','course_maintenance');
define('TOOL_VISIO','visio');
define('TOOL_VISIO_CONFERENCE','visio_conference');
define('TOOL_VISIO_CLASSROOM','visio_classroom');
define('TOOL_SURVEY','survey');
define('TOOL_WIKI','wiki');
define('TOOL_GLOSSARY','glossary');
define('TOOL_GRADEBOOK','gradebook');
define('TOOL_NOTEBOOK','notebook');

// CONSTANTS defining dokeos sections
define('SECTION_CAMPUS', 'mycampus');
define('SECTION_COURSES', 'mycourses');
define('SECTION_MYPROFILE', 'myprofile');
define('SECTION_MYAGENDA', 'myagenda');
define('SECTION_COURSE_ADMIN', 'course_admin');
define('SECTION_PLATFORM_ADMIN', 'platform_admin');
define('SECTION_MYGRADEBOOK', 'mygradebook');

// CONSTANT name for local authentication source
define('PLATFORM_AUTH_SOURCE', 'platform');

//CONSTANT defining the default HotPotatoes files directory
define('DIR_HOTPOTATOES','/HotPotatoes_files');

// This constant is a result of Windows OS detection, it has a boolean value:
// true whether the server runs on Windows OS, false otherwise.
define ('IS_WINDOWS_OS', api_is_windows_os());

/*
==============================================================================
		PROTECTION FUNCTIONS
		use these to protect your scripts
==============================================================================
*/
/**
* Function used to protect a course script.
* The function blocks access when
* - there is no $_SESSION["_course"] defined; or
* - $is_allowed_in_course is set to false (this depends on the course
* visibility and user status).
*
* This is only the first proposal, test and improve!
* @param	boolean	Option to print headers when displaying error message. Default: false
* @todo replace global variable
* @author Roan Embrechts
*/
function api_protect_course_script($print_headers=false) {	
 	global $is_allowed_in_course; 	 	 	
	//if (!isset ($_SESSION["_course"]) || !$is_allowed_in_course)	
	if (!$is_allowed_in_course) {			
		api_not_allowed($print_headers);
	}
}




/**
* Function used to protect an admin script.
* The function blocks access when the user has no platform admin rights.
* This is only the first proposal, test and improve!
*
* @author Roan Embrechts
*/
function api_protect_admin_script($allow_sessions_admins=false) {
	if (!api_is_platform_admin($allow_sessions_admins)) {
		include (api_get_path(INCLUDE_PATH)."header.inc.php");
		api_not_allowed();
	}
}

/**
* Function used to prevent anonymous users from accessing a script.
*
* @author Roan Embrechts
*/
function api_block_anonymous_users() {
	global $_user;

	if (!(isset ($_user['user_id']) && $_user['user_id']) || api_is_anonymous($_user['user_id'],true)) {
		include (api_get_path(INCLUDE_PATH)."header.inc.php");
		api_not_allowed();
	}
}

/*
==============================================================================
		ACCESSOR FUNCTIONS
		don't access kernel variables directly,
		use these functions instead
==============================================================================
*/
/**
*	@return an array with the navigator name and version
*/
function api_get_navigator() {
	$navigator = 'Unknown';
	$version = 0;
	if (strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')) {
		$navigator = 'Opera';
		list (, $version) = explode('Opera', $_SERVER['HTTP_USER_AGENT']);
	} elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
		$navigator = 'Internet Explorer';
		list (, $version) = explode('MSIE', $_SERVER['HTTP_USER_AGENT']);
	} elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Gecko')) {
		$navigator = 'Mozilla';
		list (, $version) = explode('; rv:', $_SERVER['HTTP_USER_AGENT']);
	} elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Netscape')) {
		$navigator = 'Netscape';
		list (, $version) = explode('Netscape', $_SERVER['HTTP_USER_AGENT']);
	}
	$version = doubleval($version);
	if (!strstr($version, '.')) {
		$version = number_format(doubleval($version), 1);
	}
	return array ('name' => $navigator, 'version' => $version);
}
/**
*	@return True if user selfregistration is allowed, false otherwise.
*/
function api_is_self_registration_allowed() {
	if(isset($GLOBALS['allowSelfReg'])) {
		return $GLOBALS["allowSelfReg"];
	} else {
		return false;
	}
}
/**
*	Returns a full path to a certain Dokeos area, which you specify
*	through a parameter.
*
*	See $_configuration['course_folder'] in the configuration.php
*	to alter the WEB_COURSE_PATH and SYS_COURSE_PATH parameters.
*
*	@param one of the following constants:
*	WEB_PATH, SYS_PATH, REL_PATH, WEB_COURSE_PATH, SYS_COURSE_PATH,
*	REL_COURSE_PATH, REL_CODE_PATH, WEB_CODE_PATH, SYS_CODE_PATH,
*	SYS_LANG_PATH, WEB_IMG_PATH, GARBAGE_PATH, PLUGIN_PATH, SYS_ARCHIVE_PATH,
*	INCLUDE_PATH, LIBRARY_PATH, CONFIGURATION_PATH
*
* 	@example assume that your server root is /var/www/ dokeos is installed in a subfolder dokeos/ and the URL of your campus is http://www.mydokeos.com
* 	The other configuration paramaters have not been changed.
* 	The different api_get_paths will give
* 	WEB_PATH			http://www.mydokeos.com
* 	SYS_PATH			/var/www/
* 	REL_PATH			dokeos/
* 	WEB_COURSE_PATH		http://www.mydokeos.com/courses/
* 	SYS_COURSE_PATH		/var/www/dokeos/courses/
*	REL_COURSE_PATH
* 	REL_CODE_PATH
* 	WEB_CODE_PATH
* 	SYS_CODE_PATH
* 	SYS_LANG_PATH
* 	WEB_IMG_PATH
* 	GARBAGE_PATH
* 	PLUGIN_PATH
* 	SYS_ARCHIVE_PATH
*	INCLUDE_PATH
* 	LIBRARY_PATH
* 	CONFIGURATION_PATH
*/
function api_get_path($path_type) {
	global $_configuration;
	if (!isset($_configuration['access_url']) || $_configuration['access_url']==1 || $_configuration['access_url']=='') {
		//by default we call the $_configuration['root_web'] we don't query to the DB
		//$url_info= api_get_access_url(1);
		//$root_web = $url_info['url'];		
		$root_web = $_configuration['root_web'];
	} else {
		//we look into the DB the function api_get_access_url 
		//this funcion have a problem because we can't called to the Database:: functions
		$url_info= api_get_access_url($_configuration['access_url']);		
		if ($url_info['active']==1) {
			$root_web = $url_info['url'];
		} else {
			$root_web = $_configuration['root_web'];
		}
	}
	 
	switch ($path_type) {
		case WEB_PATH :
			// example: http://www.mydokeos.com/ or http://www.mydokeos.com/portal/ if you're using
			// a subdirectory of your document root for Dokeos
			if (substr($root_web,-1) == '/') {
				return $root_web;
			} else {
				return $root_web.'/';
			}
			break;

		case SYS_PATH :
			// example: /var/www/
			if (substr($_configuration['root_sys'],-1) == '/') {
				return $_configuration['root_sys'];
			} else {
				return $_configuration['root_sys'].'/';				
			}
			break;

		case REL_PATH :
			// example: dokeos/
			if (substr($_configuration['url_append'], -1) === '/') {
				return $_configuration['url_append'];
			} else {
				return $_configuration['url_append'].'/';
			}
			break;

		case WEB_COURSE_PATH :
			// example: http://www.mydokeos.com/courses/
			return $root_web.$_configuration['course_folder'];
			break;

		case SYS_COURSE_PATH :
			// example: /var/www/dokeos/courses/
			return $_configuration['root_sys'].$_configuration['course_folder'];
			break;

		case REL_COURSE_PATH :
			// example: courses/ or dokeos/courses/
			return api_get_path(REL_PATH).$_configuration['course_folder'];
			break;
		case REL_CODE_PATH :
			// example: main/ or dokeos/main/
			return api_get_path(REL_PATH).$_configuration['code_append'];
			break;
		case WEB_CODE_PATH :
			// example: http://www.mydokeos.com/main/
			//return $GLOBALS['clarolineRepositoryWeb']; // this was changed
			return $root_web.$_configuration['code_append'];
			break;
		case SYS_CODE_PATH :
			// example: /var/www/dokeos/main/
			return $GLOBALS['clarolineRepositorySys'];
			break;
		case SYS_LANG_PATH :
			// example: /var/www/dokeos/main/lang/
			return api_get_path(SYS_CODE_PATH).'lang/';
			break;
		case WEB_IMG_PATH :
			// example: http://www.mydokeos.com/main/img/
			return api_get_path(WEB_CODE_PATH).'img/';
			break;
		case GARBAGE_PATH :
			// example: /var/www/dokeos/main/garbage/
			return $GLOBALS['garbageRepositorySys'];
			break;
		case SYS_PLUGIN_PATH :
			// example: /var/www/dokeos/plugin/
			return api_get_path(SYS_PATH).'plugin/';
			break;
		case WEB_PLUGIN_PATH :
			// example: http://www.mydokeos.com/plugin/
			return api_get_path(WEB_PATH).'plugin/';
			break;
		case SYS_ARCHIVE_PATH :
			// example: /var/www/dokeos/archive/
			return api_get_path(SYS_PATH).'archive/';
			break;
		case INCLUDE_PATH :
			// Generated by main/inc/global.inc.php 
			// example: /var/www/dokeos/main/inc/
			return str_replace('\\', '/', $GLOBALS['includePath']).'/';
			break;
		case LIBRARY_PATH :
			// example: /var/www/dokeos/main/inc/lib/
			return api_get_path(INCLUDE_PATH).'lib/';
			break;
		case WEB_LIBRARY_PATH :
			// example: http://www.mydokeos.com/main/inc/lib/
			return api_get_path(WEB_CODE_PATH).'inc/lib/';
			break;
		case CONFIGURATION_PATH :
			// example: /var/www/dokeos/main/inc/conf/
			return api_get_path(INCLUDE_PATH).'conf/';
			break;
		default :
			return;
			break;
	}
}

/**
* This function returns the id of the user which is stored in the $_user array.
*
* @example The function can be used to check if a user is logged in
* 			if (api_get_user_id())
* @return integer the id of the current user
*/
function api_get_user_id() {
	if (empty($GLOBALS['_user']['user_id'])) {
		return 0;
	}
	return $GLOBALS['_user']['user_id'];
}
/**
 * Get the list of courses a specific user is subscribed to
 * @param	int		User ID
 * @param	boolean	Whether to get session courses or not - NOT YET IMPLEMENTED
 * @return	array	Array of courses in the form [0]=>('code'=>xxx,'db'=>xxx,'dir'=>xxx,'status'=>d)
 */
function api_get_user_courses($userid,$fetch_session=true) {
	if ($userid != strval(intval($userid))) { return array(); } //get out if not integer
	$t_course = Database::get_main_table(TABLE_MAIN_COURSE);
	$t_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$t_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$t_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$t_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	
	$sql_select_courses = "SELECT cc.code code, cc.db_name db, cc.directory dir, cu.status status
			                        FROM    $t_course       cc,
											$t_course_user   cu
			                        WHERE cc.code = cu.course_code
			                        AND   cu.user_id = '".$userid."'";
	$result = api_sql_query($sql_select_courses);
	if ($result===false) { return array(); }
	while ($row = Database::fetch_array($result))
	{
		// we only need the database name of the course
		$courses[] = $row;
	}
	return $courses;
}
/**
 * Find all the information about a user. If no paramater is passed you find all the information about the current user.
 * @param $user_id (integer): the id of the user
 * @return $user_info (array): user_id, lastname, firstname, username, email, ...
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version 21 September 2004
*/
function api_get_user_info($user_id = '') {
	global $tbl_user;
	if ($user_id == '') {
		return $GLOBALS["_user"];
	} else {
		$sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_USER)." WHERE user_id='".Database::escape_string($user_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if(Database::num_rows($result) > 0) {
			$result_array = mysql_fetch_array($result);
			// this is done so that it returns the same array-index-names
			// ideally the names of the fields of the user table are renamed so that they match $_user (or vice versa)
			// $_user should also contain every field of the user table (except password maybe). This would make the
			// following lines obsolete (and the code cleaner and slimmer !!!
			$user_info['firstName'] = $result_array['firstname'];
			$user_info['lastName'] = $result_array['lastname'];
			$user_info['mail'] = $result_array['email'];
			$user_info['picture_uri'] = $result_array['picture_uri'];
			$user_info['user_id'] = $result_array['user_id'];
			$user_info['official_code'] = $result_array['official_code'];
			$user_info['status'] = $result_array['status'];
			$user_info['auth_source'] = $result_array['auth_source'];
			$user_info['username'] = $result_array['username'];
			$user_info['theme'] = $result_array['theme'];
			return $user_info;
		}
		return false;
	}
}
/**
 * Find all the information about a user from username instead of user id
 * @param $username (string): the username
 * @return $user_info (array): user_id, lastname, firstname, username, email, ...
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
*/
function api_get_user_info_from_username($username = '') {
    if (empty($username)) { return false; }
    global $tbl_user;
    $sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_USER)." WHERE username='".Database::escape_string($username)."'";
    $result = api_sql_query($sql, __FILE__, __LINE__);
    if (Database::num_rows($result) > 0) {
        $result_array = mysql_fetch_array($result);
        // this is done so that it returns the same array-index-names
        // ideally the names of the fields of the user table are renamed so that they match $_user (or vice versa)
        // $_user should also contain every field of the user table (except password maybe). This would make the
        // following lines obsolete (and the code cleaner and slimmer !!!
        $user_info['firstName'] = $result_array['firstname'];
        $user_info['lastName'] = $result_array['lastname'];
        $user_info['mail'] = $result_array['email'];
        $user_info['picture_uri'] = $result_array['picture_uri'];
        $user_info['user_id'] = $result_array['user_id'];
        $user_info['official_code'] = $result_array['official_code'];
        $user_info['status'] = $result_array['status'];
        $user_info['auth_source'] = $result_array['auth_source'];
        $user_info['username'] = $result_array['username'];
        $user_info['theme'] = $result_array['theme'];
        return $user_info;
    }
    return false;
}
/**
 * Returns the current course id (integer)
*/
function api_get_course_id() {
	return $GLOBALS["_cid"];
}
/**
 * Returns the current course directory
 *
 * This function relies on api_get_course_info()
 * @param	string	The course code - optional (takes it from session if not given)
 * @return	string	The directory where the course is located inside the Dokeos "courses" directory
 * @author	Yannick Warnier <yannick.warnier@dokeos.com>
*/
function api_get_course_path($course_code=null) {
	if(!empty($course_code)) {
		$info = api_get_course_info($course_code);
	} else {
		$info = api_get_course_info();
	}
	return $info['path'];
}
/**
 * Gets a course setting from the current course_setting table. Try always using integer values.
 * @param	string	The name of the setting we want from the table
 * @param	string	Optional: course code
 * @return	mixed	The value of that setting in that table. Return -1 if not found.
 */
function api_get_course_setting($setting_name, $course_code = null) {
	if (!empty($course_code)) {
		$c = api_get_course_info($course_code);
		$table = Database::get_course_table(TABLE_COURSE_SETTING,$c['dbName']);
	} else {
		$table = Database::get_course_table(TABLE_COURSE_SETTING);
	}
	$setting_name = mysql_real_escape_string($setting_name);
	$sql = "SELECT * FROM $table WHERE variable = '$setting_name'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (Database::num_rows($res)>0) {
		$row = Database::fetch_array($res);
		return $row['value'];
	}
	return -1;
}
/**
 * Gets an anonymous user ID
 * 
 * For some tools that need tracking, like the learnpath tool, it is necessary
 * to have a usable user-id to enable some kind of tracking, even if not
 * perfect. An anonymous ID is taken from the users table by looking for a
 * status of "6" (anonymous).
 * @return	int	User ID of the anonymous user, or O if no anonymous user found
 */
function api_get_anonymous_id() {
	$table = Database::get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT user_id FROM $table WHERE status = 6";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (Database::num_rows($res)>0) {
		$row = Database::fetch_array($res);
		//error_log('api_get_anonymous_id() returns '.$row['user_id'],0);
		return $row['user_id'];
	} else {//no anonymous user was found
		return 0;
	}
}

/**
 * Returns the cidreq parameter name + current course id
*/
function api_get_cidreq() {
	if (!empty ($GLOBALS["_cid"])) {
		return 'cidReq='.htmlspecialchars($GLOBALS["_cid"]);
	}
	return '';
}
/**
*	Returns the current course info array.
*	Note: this array is only defined if the user is inside a course.
*	Array elements:
*	['name']
*	['official_code']
*	['sysCode']
*	['path']
*	['dbName']
*	['dbNameGlu']
*	['titular']
*	['language']
*	['extLink']['url' ]
*	['extLink']['name']
*	['categoryCode']
*	['categoryName']
*	Now if the course_code is given, the returned array gives info about that
*   particular course, not specially the current one.
* @todo	same behaviour as api_get_user_info so that api_get_course_id becomes absolete too
*/
function api_get_course_info($course_code=null) {
	if (!empty($course_code)) {
		$course_code = Database::escape_string($course_code);
		$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    	$course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql =    "SELECT `course`.*, `course_category`.`code` `faCode`, `course_category`.`name` `faName`
                 FROM $course_table
                 LEFT JOIN $course_cat_table
                 ON `course`.`category_code` =  `course_category`.`code`
                 WHERE `course`.`code` = '$course_code'";
        $result = api_sql_query($sql,__FILE__,__LINE__);
        $_course = array();
        if (Database::num_rows($result)>0) {
        	global $_configuration;
            $cData = Database::fetch_array($result);
			$_course['id'          ]         = $cData['code'             ]; //auto-assigned integer
			$_course['name'        ]         = $cData['title'         ];
            $_course['official_code']         = $cData['visual_code'        ]; // use in echo
            $_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
            $_course['path'        ]         = $cData['directory'        ]; // use as key in path
            $_course['dbName'      ]         = $cData['db_name'           ]; // use as key in db list
            $_course['dbNameGlu'   ]         = $_configuration['table_prefix'] . $cData['db_name'] . $_configuration['db_glue']; // use in all queries
            $_course['titular'     ]         = $cData['tutor_name'       ];
            $_course['language'    ]         = $cData['course_language'   ];
            $_course['extLink'     ]['url' ] = $cData['department_url'    ];
            $_course['extLink'     ]['name'] = $cData['department_name'];
            $_course['categoryCode']         = $cData['faCode'           ];
            $_course['categoryName']         = $cData['faName'           ];

            $_course['visibility'  ]         = $cData['visibility'];
            $_course['subscribe_allowed']    = $cData['subscribe'];
			$_course['unubscribe_allowed']   = $cData['unsubscribe'];
        }
        return $_course;			
	} else {
		global $_course;
		return $_course;
	}
}

/*
==============================================================================
		DATABASE QUERY MANAGEMENT
==============================================================================
*/
/**
 * Executes an SQL query
 * You have to use addslashes() on each value that you want to record into the database
 *
 * @author Olivier Brouckaert
 * @param  string $query - SQL query
 * @param  string $file - optional, the file path and name of the error (__FILE__)
 * @param  string $line - optional, the line of the error (__LINE__)
 * @return resource - the return value of the query
 */
function api_sql_query($query, $file = '', $line = 0) {
	$result = @mysql_query($query);

	if ($line && !$result) {
			$info = '<pre>';
			$info .= '<b>MYSQL ERROR :</b><br/> ';
			$info .= mysql_error();
			$info .= '<br/>';
			$info .= '<b>QUERY       :</b><br/> ';
			$info .= $query;
			$info .= '<br/>';
			$info .= '<b>FILE        :</b><br/> ';
			$info .= ($file == '' ? ' unknown ' : $file);
			$info .= '<br/>';
			$info .= '<b>LINE        :</b><br/> ';
			$info .= ($line == 0 ? ' unknown ' : $line);
			$info .= '</pre>';
			//@ mysql_close();
			//die($info);
			echo $info;
	}
	return $result;
}
/**
 * Store the result of a query into an array
 *
 * @author Olivier Brouckaert
 * @param  resource $result - the return value of the query
 * @return array - the value returned by the query
 */
function api_store_result($result) {
	$tab = array ();
	while ($row = mysql_fetch_array($result)) {
		$tab[] = $row;
	}
	return $tab;
}

/*
==============================================================================
		SESSION MANAGEMENT
==============================================================================
*/
/**
 * Start the Dokeos session.
 * 
 * The default lifetime for session is set here. It is not possible to have it
 * as a database setting as it is used before the database connection has been made.
 * It is taken from the configuration file, and if it doesn't exist there, it is set
 * to 360000 seconds
 *
 * @author Olivier Brouckaert
 * @param  string variable - the variable name to save into the session
 */
function api_session_start($already_installed = true) {
	global $storeSessionInDb;
	global $_configuration;
	
	/* causes too many problems and is not configurable dynamically
	 * 
	if($already_installed){
		$session_lifetime = 360000;
		if(isset($_configuration['session_lifetime']))
		{
			$session_lifetime = $_configuration['session_lifetime'];	
		}
		session_set_cookie_params($session_lifetime,api_get_path(REL_PATH));
		
	}*/
	if (is_null($storeSessionInDb)) {
		$storeSessionInDb = false;
	}
	if ($storeSessionInDb && function_exists('session_set_save_handler')) {
		include_once (api_get_path(LIBRARY_PATH).'session_handler.class.php');
		$session_handler = new session_handler();
		@ session_set_save_handler(array (& $session_handler, 'open'), array (& $session_handler, 'close'), array (& $session_handler, 'read'), array (& $session_handler, 'write'), array (& $session_handler, 'destroy'), array (& $session_handler, 'garbage'));
	}
	session_name('dk_sid');
	session_start();
	if ($already_installed) {
		if (empty ($_SESSION['checkDokeosURL'])) {
			$_SESSION['checkDokeosURL'] = api_get_path(WEB_PATH);
		} elseif ($_SESSION['checkDokeosURL'] != api_get_path(WEB_PATH)) {
			api_session_clear();
		}
	}
}
/**
 * save a variable into the session
 *
 * BUG: function works only with global variables
 *
 * @author Olivier Brouckaert
 * @param  string variable - the variable name to save into the session
 */
function api_session_register($variable) {
	global $$variable;
	session_register($variable);
	$_SESSION[$variable] = $$variable;
}
/**
 * Remove a variable from the session.
 *
 * @author Olivier Brouckaert
 * @param  string variable - the variable name to remove from the session
 */
function api_session_unregister($variable) {
	$variable = strval($variable);
	if(isset($GLOBALS[$variable])) {
		unset ($GLOBALS[$variable]);
	}
	
	if(isset($_SESSION[$variable])) {
		$_SESSION[$variable] = null;
		session_unregister($variable);		
	}	
}
/**
 * Clear the session
 *
 * @author Olivier Brouckaert
 */
function api_session_clear() {
	session_regenerate_id();
	session_unset();
	$_SESSION = array ();
}
/**
 * Destroy the session
 *
 * @author Olivier Brouckaert
 */
function api_session_destroy() {
	session_unset();
	$_SESSION = array ();
	session_destroy();
}

/*
==============================================================================
		STRING MANAGEMENT
==============================================================================
*/
/**
 * Add a parameter to the existing URL. If this parameter already exists,
 * just replace it with the new value
 * @param   string  The URL
 * @param   string  param=value string
 * @param   boolean Whether to filter XSS or not
 * @return  string  The URL with the added parameter
 */
function api_add_url_param($url, $param, $filter_xss=true) {
	if (empty ($param)) {
		return $url;
	}
	if (strstr($url, '?')) {
		if ($param[0] != '&') {
			$param = '&'.$param;
		}
		list (, $query_string) = explode('?', $url);
		$param_list1 = explode('&', $param);
		$param_list2 = explode('&', $query_string);
		$param_list1_keys = $param_list1_vals = array ();
		foreach ($param_list1 as $key => $enreg) {
			list ($param_list1_keys[$key], $param_list1_vals[$key]) = explode('=', $enreg);
		}
		$param_list1 = array ('keys' => $param_list1_keys, 'vals' => $param_list1_vals);
		foreach ($param_list2 as $enreg) {
			$enreg = explode('=', $enreg);
			$key = array_search($enreg[0], $param_list1['keys']);
			if (!is_null($key) && !is_bool($key)) {
				$url = str_replace($enreg[0].'='.$enreg[1], $enreg[0].'='.$param_list1['vals'][$key], $url);
				$param = str_replace('&'.$enreg[0].'='.$param_list1['vals'][$key], '', $param);
			}
		}
		$url .= $param;
	} else {
		$url = $url.'?'.$param;
	}
    if ($filter_xss === true) {
    	$url = Security::remove_XSS($url);
    }
	return $url;
}
/**
* Returns a difficult to guess password.
* @param int $length, the length of the password
* @return string the generated password
*/
function api_generate_password($length = 8) {
	$characters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	if ($length < 2) {
		$length = 2;
	}
	$password = '';
	for ($i = 0; $i < $length; $i ++) {
		$password .= $characters[rand() % strlen($characters)];
	}
	return $password;
}
/**
* Checks a password to see wether it is OK to use.
* @param string $password
* @return true if the password is acceptable, false otherwise
*/
function api_check_password($password) {
	$lengthPass = strlen($password);
	if ($lengthPass < 5) {
		return false;
	}
	$passLower = strtolower($password);
	$cptLettres = $cptChiffres = 0;
	$consecutif = 0;
	$codeCharPrev = 0;
	for ($i = 0; $i < $lengthPass; $i ++) {
		$codeCharCur = ord($passLower[$i]);
		if ($i && abs($codeCharCur - $codeCharPrev) <= 1) {
			$consecutif ++;
			if ($consecutif == 3) {
				return false;
			}
		} else {
			$consecutif = 1;
		}
		if ($codeCharCur >= 97 && $codeCharCur <= 122) {
			$cptLettres ++;
		} elseif ($codeCharCur >= 48 && $codeCharCur <= 57) {
			$cptChiffres ++;
		} else {
			return false;
		}
		$codeCharPrev = $codeCharCur;
	}
	return ($cptLettres >= 3 && $cptChiffres >= 2) ? true : false;
}
/**
 * Clear the user ID from the session if it was the anonymous user. Generally
 * used on out-of-tools pages to remove a user ID that could otherwise be used
 * in the wrong context.
 * This function is to be used in conjunction with the api_set_anonymous()
 * function to simulate the user existence in case of an anonymous visit.
 * @param	bool	database check switch - passed to api_is_anonymous()
 * @return	bool	true if succesfully unregistered, false if not anonymous. 
 */
function api_clear_anonymous($db_check=false) {
	global $_user;
	if (api_is_anonymous($_user['user_id'],$db_check)) {
		unset($_user['user_id']);
		api_session_unregister('_uid');
		return true;
	} else {
		return false;
	}
}

/**
 * truncates a string
 *
 * @author Brouckaert Olivier
 * @param  string text - text to truncate
 * @param  integer length - length of the truncated text
 * @param  string endStr - suffix
 * @param  boolean middle - if true, truncates on string middle
 */
function api_trunc_str($text, $length = 30, $endStr = '...', $middle = false) {
	if (strlen($text) <= $length) {
		return $text;
	}
	if ($middle) {
		$text = rtrim(substr($text, 0, round($length / 2))).$endStr.ltrim(substr($text, -round($length / 2)));
	} else {
		$text = rtrim(substr($text, 0, $length)).$endStr;
	}
	return $text;
}
// deprecated, use api_trunc_str() instead
function shorten($input, $length = 15) {
	$length = intval($length);
	if (!$length) {
		$length = 15;
	}
	return api_trunc_str($input, $length);
}
/**
 * handling simple and double apostrofe in order that strings be stored properly in database
 *
 * @author Denes Nagy
 * @param  string variable - the variable to be revised
 */
function domesticate($input) {
	$input = stripslashes($input);
	$input = str_replace("'", "''", $input);
	$input = str_replace('"', "''", $input);
	return ($input);
}

/**
* Returns the status string corresponding to the status code
* @author Noel Dieschburg
* @param the int status code
*/
function get_status_from_code($status_code)
{
        switch ($status_code) {
        case STUDENT:
        return get_lang('Student');
        case TEACHER:
        return get_lang('Teacher');
        case COURSEMANAGER:
        return get_lang('Manager');
        case SESSIONADMIN:
        return get_lang('SessionsAdmin');
        case DRH:
        return get_lang('Drh');

        }

}



/*
==============================================================================
		FAILURE MANAGEMENT
==============================================================================
*/

/*
 * The Failure Management module is here to compensate
 * the absence of an 'exception' device in PHP 4.
 */
/**
 * $api_failureList - array containing all the failure recorded
 * in order of arrival.
 */
$api_failureList = array ();
/**
 * Fills a global array called $api_failureList
 * This array collects all the failure occuring during the script runs
 * The main purpose is allowing to manage the display messages externaly
 * from the functions or objects. This strengthens encupsalation principle
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  string $failureType - the type of failure
 * @global array $api_failureList
 * @return bolean false to stay consistent with the main script
 */
function api_set_failure($failureType) {
	global $api_failureList;
	$api_failureList[] = $failureType;
	return false;
}

/**
 * Sets the current user as anonymous if it hasn't been identified yet. This 
 * function should be used inside a tool only. The function api_clear_anonymous()
 * acts in the opposite direction by clearing the anonymous user's data every
 * time we get on a course homepage or on a neutral page (index, admin, my space)
 * @return	bool	true if set user as anonymous, false if user was already logged in or anonymous id could not be found
 */
function api_set_anonymous() {
	global $_user;
	if(!empty($_user['user_id'])) {
		return false;
	} else {
		$user_id = api_get_anonymous_id();
		if($user_id == 0) {
			return false;
		} else {
			api_session_unregister('_user');
			$_user['user_id'] = $user_id;
			$_user['is_anonymous'] = true;
			api_session_register('_user');
			$GLOBALS['_user'] = $_user;
			return true;
		}
	}
}

/**
 * get the last failure stored in $api_failureList;
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param void
 * @return string - the last failure stored
 */
function api_get_last_failure() {
	global $api_failureList;
	return $api_failureList[count($api_failureList) - 1];
}
/**
 * collects and manage failures occuring during script execution
 * The main purpose is allowing to manage the display messages externaly
 * from functions or objects. This strengthens encupsalation principle
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @package dokeos.library
 */
class api_failure
{
	/*
	 * IMPLEMENTATION NOTE : For now the $api_failureList list is set to the
	 * global scope, as PHP 4 is unable to manage static variable in class. But
	 * this feature is awaited in PHP 5. The class is already written to minize
	 * the change when static class variable will be possible. And the API won't
	 * change.
	 */
	public $api_failureList = array ();
	/**
	 * Pile the last failure in the failure list
	 *
	 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
	 * @param  string $failureType - the type of failure
	 * @global array  $api_failureList
	 * @return bolean false to stay consistent with the main script
	 */
	function set_failure($failureType) {
		global $api_failureList;
		$api_failureList[] = $failureType;
		return false;
	}
	/**
	 * get the last failure stored
	 *
	 * @author Hugues Peeters <hugues.peeters@claroline.net>
	 * @param void
	 * @return string - the last failure stored
	 */
	function get_last_failure() {
		global $api_failureList;
        if(count($api_failureList)==0){return '';}
		return $api_failureList[count($api_failureList) - 1];
	}
}

/*
==============================================================================
		CONFIGURATION SETTINGS
==============================================================================
*/
/**
* DEPRECATED, use api_get_setting instead
*/
function get_setting($variable, $key = NULL) {
	global $_setting;
	return api_get_setting($variable, $key);
}

/**
 * Gets the current Dokeos (not PHP/cookie) session ID, if active
 * @return  int     O if no active session, the session ID otherwise
 */
function api_get_session_id() {
	if (empty($_SESSION['id_session'])) {
		return 0;
	} else {
		return (int) $_SESSION['id_session'];
	}
}
/**
 * Gets the current or given session name
 * @param   int     Session ID (optional)
 * @return  string  The session name, or null if unfound
 */
function api_get_session_name($session_id) {
    if (empty($session_id)) {
        $session_id = api_get_session_id();
        if (empty($session_id)) {return null;}
    }
    $t = Database::get_main_table(TABLE_MAIN_SESSION);
    $s = "SELECT name FROM $t WHERE id = ".(int)$session_id;
    $r = api_sql_query($s,__FILE__,__LINE__);
    $c = Database::num_rows($r);
    if ($c > 0) {
        //technically, there can be only one, but anyway we take the first
        $rec = Database::fetch_array($r);
        return $rec['name'];
    }
    return null;
}
/**
* Returns the value of a setting from the web-adjustable admin config settings.
*
* WARNING true/false are stored as string, so when comparing you need to check e.g.
* if(api_get_setting("show_navigation_menu") == "true") //CORRECT
* instead of
* if(api_get_setting("show_navigation_menu") == true) //INCORRECT
* @param	string	The variable name
* @param	string	The subkey (sub-variable) if any. Defaults to NULL
* @author Rene Haentjens
* @author Bart Mollet
*/
function api_get_setting($variable, $key = NULL) {
	global $_setting;
	return is_null($key) ? (!empty($_setting[$variable])?$_setting[$variable]:null) : $_setting[$variable][$key];
}

/**
 * Returns an escaped version of $_SERVER['PHP_SELF'] to avoid XSS injection
 * @return	string	Escaped version of $_SERVER['PHP_SELF']
 */
function api_get_self() {
	return htmlentities($_SERVER['PHP_SELF']);
}

/*
==============================================================================
		LANGUAGE SUPPORT
==============================================================================
*/

/**
* Whenever the server type in the Dokeos Config settings is
* set to test/development server you will get an indication that a language variable
* is not translated and a link to a suggestions form of DLTT.
*
* @return language variable '$lang'.$variable or language variable $variable.
*
* @author Roan Embrechts
* @author Patrick Cool
* Reworked by Ivan Tcholakov, APR-2009
*/
function get_lang($variable, $notrans = 'DLTT', $language = null) {

	// We introduce the possibility to request specific language
	// by the aditional parameter $language to this function.

	// By manipulating this global variable the translation
	// may be done in different languages too (not the elegant way).
	global $language_interface;

	// Because of possibility for manipulations of the global variable
	// $language_interface, we need its initial value.
	global $language_interface_initial_value;

	// This is a cache for already translated language variables.
	// By using it we will avoid repetitive translations.
	static $cache = array();

	// Combining both ways for requesting specific language.
	if (empty($language))
	{
		$language = $language_interface;
	}

	// This is a flag for showing the link to the Dokeos Language Translation Tool
	// when the requested variable has no translation within the language files.
	$dltt = $notrans == 'DLTT' ? true : false;

	// Cache initialization.
	if (!is_array($cache[$language]))
	{
		$cache[$language] = array(false => array(), true => array());
	}

	// Looking up into the cache for existing translation.
	if (isset($cache[$language][$dltt][$variable])) {
		// There is a previously saved translation, returning it.
		return $cache[$language][$dltt][$variable];
	}

	// There is no saved translation, we have to extract it.

	// If the language files have been reloaded, then the language
	// variables should be accessed as local ones.
	$seek_local_variables = false;

	// We reload the language variables when the requested language is different to
	// the language of the interface or when the server is in testing mode.
	if ($language != $language_interface_initial_value || api_get_setting('server_type') == 'test') {
		$seek_local_variables = true;
		global $language_files;
		$langpath = api_get_path(SYS_CODE_PATH).'lang/';

		if (isset ($language_files)) {
			if (!is_array($language_files)) {
				@include $langpath.$language.'/'.$language_files.'.inc.php';
			} else {
				foreach ($language_files as $index => $language_file) {
					@include $langpath.$language.'/'.$language_file.'.inc.php';
				}
			}
		}
	}

	$ot = '[='; //opening tag for missing vars
	$ct = '=]'; //closing tag for missing vars
	if (api_get_setting('hide_dltt_markup') == 'true') {
		$ot = '';
		$ct = '';
	}

	// Translation mode for production servers.

	if (api_get_setting('server_type') != 'test') {
		if (!$seek_local_variables) {
			$lvv = isset ($GLOBALS['lang'.$variable]) ? $GLOBALS['lang'.$variable] : (isset ($GLOBALS[$variable]) ? $GLOBALS[$variable] : $ot.$variable.$ct);
		} else {
			@eval('$lvv = $'.$variable.';');
			if (!isset($lvv)) {
				@eval('$lvv = $lang'.$variable.';');
				if (!isset($lvv)) {
					$lvv = $ot.$variable.$ct;
				}
			}
		}
		if (!is_string($lvv)) {
			$cache[$language][$dltt][$variable] = $lvv;
			return $lvv;
		}
		$lvv = str_replace("\\'", "'", $lvv);
		$cache[$language][$dltt][$variable] = $lvv;
		return $lvv;
	}

	// Translation mode for test/development servers.

	if (!is_string($variable)) {
		$cache[$language][$dltt][$variable] = $ot.'get_lang(?)'.$ct;
		return $cache[$language][$dltt][$variable];
	}
	@ eval ('$langvar = $'.$variable.';'); // Note (RH): $$var doesn't work with arrays, see PHP doc
	if (isset ($langvar) && is_string($langvar) && strlen($langvar) > 0) {
		$langvar = str_replace("\\'", "'", $langvar);
		$cache[$language][$dltt][$variable] = $langvar;
		return $langvar;
	}
	@ eval ('$langvar = $lang'.$variable.';');
	if (isset ($langvar) && is_string($langvar) && strlen($langvar) > 0) {
		$langvar = str_replace("\\'", "'", $langvar);
		$cache[$language][$dltt][$variable] = $langvar;
		return $langvar;
	}
	if (!$dltt) {
		$cache[$language][$dltt][$variable] = $ot.$variable.$ct;
		return $cache[$language][$dltt][$variable];
	}
	if (!is_array($language_files)) {
		$language_file = $language_files;
	} else {
		$language_file = implode('.inc.php',$language_files);
	}
	$cache[$language][$dltt][$variable] =
		$ot.$variable.$ct."<a href=\"http://www.dokeos.com/DLTT/suggestion.php?file=".$language_file.".inc.php&amp;variable=$".$variable."&amp;language=".$language_interface."\" target=\"_blank\" style=\"color:#FF0000\"><strong>#</strong></a>";
	return $cache[$language][$dltt][$variable];
}

/**
 * Gets the current interface language
 * @return string The current language of the interface
 */
function api_get_interface_language() {
	global 	$language_interface;
	return $language_interface;
}

/*
==============================================================================
		USER PERMISSIONS
==============================================================================
*/
/**
* Check if current user is a platform administrator
* @return boolean True if the user has platform admin rights,
* false otherwise.
* @see usermanager::is_admin(user_id) for a user-id specific function
*/
function api_is_platform_admin($allow_sessions_admins = false) {
	if($_SESSION['is_platformAdmin']) {
		return true;
	} else {
		global $_user;
		if ($allow_sessions_admins && $_user['status']==SESSIONADMIN) {
			return true;
		}	
	}
	return false;
}
/**
 * Check if current user is allowed to create courses
* @return boolean True if the user has course creation rights,
* false otherwise.
*/
function api_is_allowed_to_create_course() {
	return $_SESSION["is_allowedCreateCourse"];
}
/**
 * Check if the current user is a course administrator
 * @return boolean True if current user is a course administrator
 */
function api_is_course_admin() {
	return $_SESSION["is_courseAdmin"];
}
/**
 * Check if the current user is a course coach
 * @return	bool	True if current user is a course coach
 */
function api_is_course_coach() {
	return $_SESSION['is_courseCoach'];
}
/**
 * Check if the current user is a course tutor
 * @return 	bool	True if current user is a course tutor
 */
function api_is_course_tutor() {
	return $_SESSION['is_courseTutor'];
}
/**
 * Check if the current user is a course or session coach
 * @return boolean True if current user is a course or session coach
 */
function api_is_coach() {
	global $_user;
	global $sessionIsCoach;

	 $sql = "SELECT DISTINCT id, name, date_start, date_end
							FROM session
							INNER JOIN session_rel_course
								ON session_rel_course.id_coach = '".mysql_real_escape_string($_user['user_id'])."'
							ORDER BY date_start, date_end, name";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$sessionIsCoach = api_store_result($result);

	$sql = "SELECT DISTINCT id, name, date_start, date_end
							FROM session
							WHERE session.id_coach =  '".mysql_real_escape_string($_user['user_id'])."'
							ORDER BY date_start, date_end, name";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	$sessionIsCoach = array_merge($sessionIsCoach , api_store_result($result));

	if(count($sessionIsCoach) > 0) {
		return true;
	} else {
		return false;
	}
}

/**
 * Check if the current user is a session administrator
 * @return boolean True if current user is a course administrator
 */
function api_is_session_admin() {
	global $_user;
	if ($_user['status']==SESSIONADMIN) {
		return true;
	} else{
		return false;
	}
}

/*
==============================================================================
		DISPLAY OPTIONS
		student view, title, message boxes,...
==============================================================================
*/
/**
 * Displays the title of a tool.
 * Normal use: parameter is a string:
 * api_display_tool_title("My Tool")
 *
 * Optionally, there can be a subtitle below
 * the normal title, and / or a supra title above the normal title.
 *
 * e.g. supra title:
 * group
 * GROUP PROPERTIES
 *
 * e.g. subtitle:
 * AGENDA
 * calender & events tool
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @param  mixed $titleElement - it could either be a string or an array
 *                               containing 'supraTitle', 'mainTitle',
 *                               'subTitle'
 * @return void
 */
function api_display_tool_title($titleElement) {
	if (is_string($titleElement)) {
		$tit = $titleElement;
		unset ($titleElement);
		$titleElement['mainTitle'] = $tit;
	}
	echo '<h3>';
	if (!empty($titleElement['supraTitle'])) {
		echo '<small>'.$titleElement['supraTitle'].'</small><br>';
	}
	if (!empty($titleElement['mainTitle'])) {
		echo $titleElement['mainTitle'];
	}
	if (!empty($titleElement['subTitle'])) {
		echo '<br><small>'.$titleElement['subTitle'].'</small>';
	}
	echo '</h3>';
}
/**
*	Display options to switch between student view and course manager view
*
*	Changes in version 1.2 (Patrick Cool)
*	Student view switch now behaves as a real switch. It maintains its current state until the state
*	is changed explicitly
*
*	Changes in version 1.1 (Patrick Cool)
*	student view now works correctly in subfolders of the document tool
*	student view works correctly in the new links tool
*
*	Example code for using this in your tools:
*	//if ( $is_courseAdmin && api_get_setting('student_view_enabled') == 'true' )
*	//{
*	//	display_tool_view_option($isStudentView);
*	//}
*	//and in later sections, use api_is_allowed_to_edit()
*
	@author Roan Embrechts
*	@author Patrick Cool
*	@version 1.2
*	@todo rewrite code so it is easier to understand
*/
function api_display_tool_view_option() {
	if (api_get_setting('student_view_enabled') != "true") {
		return '';
	}
	$output_string='';

	$sourceurl = '';
	$is_framed = false;
	// Exceptions apply for all multi-frames pages
	if (strpos($_SERVER['REQUEST_URI'],'chat/chat_banner.php')!==false) {	//the chat is a multiframe bit that doesn't work too well with the student_view, so do not show the link
		$is_framed = true;
		return '';
	}
	// Uncomment to remove student view link from document view page
	if (strpos($_SERVER['REQUEST_URI'],'document/headerpage.php')!==false) {
		$sourceurl = str_replace('document/headerpage.php','document/showinframes.php',$_SERVER['REQUEST_URI']);
		//showinframes doesn't handle student view anyway...
		//return '';
		$is_framed = true;
	}
    // Uncomment to remove student view link from document view page
    if (strpos($_SERVER['REQUEST_URI'],'newscorm/lp_header.php')!==false) {
    	if (empty($_GET['lp_id'])) {
        	return '';
        }
        $sourceurl = substr($_SERVER['REQUEST_URI'],0,strpos($_SERVER['REQUEST_URI'],'?'));
        $sourceurl = str_replace('newscorm/lp_header.php','newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.intval($_GET['lp_id']).'&isStudentView='.($_SESSION['studentview']=='studentview'?'false':'true'),$sourceurl);
        //showinframes doesn't handle student view anyway...
        //return '';
        $is_framed = true;
    }

	// check if the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
	if (!$is_framed) {
		if (!strstr($_SERVER['REQUEST_URI'], "?")) {
			$sourceurl = api_get_self()."?".api_get_cidreq();
		} else {
			$sourceurl = $_SERVER['REQUEST_URI'];
			//$sourceurl = str_replace('&', '&amp;', $sourceurl);
		}
	}
	if(!empty($_SESSION['studentview'])) {
		if ($_SESSION['studentview']=='studentview') {
			// we have to remove the isStudentView=true from the $sourceurl
			$sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
			$sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
			$output_string .= '<a href="'.$sourceurl.'&isStudentView=false" target="_top">'.get_lang("StudentView").'</a>';
		} elseif ($_SESSION['studentview']=='teacherview') {
			//switching to teacherview
			$sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
			$sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
			$output_string .= '<a href="'.$sourceurl.'&isStudentView=true" target="_top">'.get_lang("CourseManagerview").'</a>';
		}
	} else {
		$output_string .= '<a href="'.$sourceurl.'&isStudentView=true" target="_top">'.get_lang("StudentView").'</a>';
	}
	echo $output_string;
}
/**
 * Displays the contents of an array in a messagebox.
 * @param array $info_array An array with the messages to show
 */
function api_display_array($info_array) {
	foreach ($info_array as $element) {
		$message .= $element."<br>";
	}
	Display :: display_normal_message($message);
}
/**
*	Displays debug info
* @param string $debug_info The message to display
*	@author Roan Embrechts
*	@version 1.1, March 2004
*/
function api_display_debug_info($debug_info) {
	$message = "<i>Debug info</i><br>";
	$message .= $debug_info;
	Display :: display_normal_message($message);
}
/**
*	@deprecated, use api_is_allowed_to_edit() instead
*/
function is_allowed_to_edit() {
	return api_is_allowed_to_edit();
}

/**
*	Function that removes the need to directly use is_courseAdmin global in
*	tool scripts. It returns true or false depending on the user's rights in
*	this particular course.
*	Optionally checking for tutor and coach roles here allows us to use the
*	student_view feature altogether with these roles as well.
*	@param	bool	Whether to check if the user has the tutor role
*	@param	bool	Whether to check if the user has the coach role
*
*	@author Roan Embrechts
*	@author Patrick Cool
*	@version 1.1, February 2004
*	@return boolean, true: the user has the rights to edit, false: he does not
*/
function api_is_allowed_to_edit($tutor=false,$coach=false) {	
	$is_courseAdmin = api_is_course_admin() || api_is_platform_admin();
	if (!$is_courseAdmin && $tutor == true) {	//if we also want to check if the user is a tutor...
		$is_courseAdmin = $is_courseAdmin || api_is_course_tutor();
	}
	if (!$is_courseAdmin && $coach == true) {	//if we also want to check if the user is a coach...';
		$is_courseAdmin = $is_courseAdmin || api_is_course_coach();
	}	
	if (api_get_setting('student_view_enabled') == 'true') {	//check if the student_view is enabled, and if so, if it is activated
		$is_allowed = $is_courseAdmin && $_SESSION['studentview'] != "studentview";
		return $is_allowed;
	} else {
		return $is_courseAdmin;
	}
		
}

/**
* this fun
* @param $tool the tool we are checking ifthe user has a certain permission
* @param $action the action we are checking (add, edit, delete, move, visibility)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @version 1.0
*/
function api_is_allowed($tool, $action, $task_id = 0) {
	global $_course;
	global $_user;

	if (api_is_course_admin()) {
		return true;
	}
	//if(!$_SESSION['total_permissions'][$_course['code']] and $_course)
	if (is_array($_course) and count($_course)>0) {
		require_once(api_get_path(SYS_CODE_PATH) . 'permissions/permissions_functions.inc.php');
		require_once(api_get_path(LIBRARY_PATH) . "/groupmanager.lib.php");

		// getting the permissions of this user
		if ($task_id == 0) {
			$user_permissions = get_permissions('user', $_user['user_id']);
			$_SESSION['total_permissions'][$_course['code']] = $user_permissions;
		}

		// getting the permissions of the task
		if ($task_id != 0) {
			$task_permissions = get_permissions('task', $task_id);
			/* !!! */$_SESSION['total_permissions'][$_course['code']] = $task_permissions;
		}
		//print_r($_SESSION['total_permissions']);

		// getting the permissions of the groups of the user
		//$groups_of_user = GroupManager::get_group_ids($_course['db_name'], $_user['user_id']);

		//foreach($groups_of_user as $group)
			//$this_group_permissions = get_permissions('group', $group);

		// getting the permissions of the courseroles of the user
		$user_courserole_permissions = get_roles_permissions('user', $_user['user_id']);

		// getting the permissions of the platformroles of the user
		//$user_platformrole_permissions = get_roles_permissions('user', $_user['user_id'], ', platform');

		// getting the permissions of the roles of the groups of the user
		//foreach($groups_of_user as $group)
			//$this_group_courserole_permissions = get_roles_permissions('group', $group);

		// getting the permissions of the platformroles of the groups of the user
		//foreach($groups_of_user as $group)
			//$this_group_platformrole_permissions = get_roles_permissions('group', $group, 'platform');
	}

	// ifthe permissions are limited we have to map the extended ones to the limited ones
	if (api_get_setting('permissions') == 'limited') {
		if ($action == 'Visibility') {
			$action = 'Edit';
		}
		if($action == 'Move') {
			$action = 'Edit';
		}
	}

	// the session that contains all the permissions already exists for this course
	// so there is no need to requery everything.
	//my_print_r($_SESSION['total_permissions'][$_course['code']][$tool]);
	if (is_array($_SESSION['total_permissions'][$_course['code']][$tool])) {
		if (in_array($action, $_SESSION['total_permissions'][$_course['code']][$tool])) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Tells whether this user is an anonymous user
 * @param	int		User ID (optional, will take session ID if not provided)
 * @param	bool	Whether to check in the database (true) or simply in the session (false) to see if the current user is the anonymous user
 * @return	bool	true if this user is anonymous, false otherwise
 */
function api_is_anonymous($user_id=null,$db_check=false) {
	if(!isset($user_id)) {
		$user_id = api_get_user_id();
	}
	if($db_check) {
		$info = api_get_user_info($user_id);
		if($info['status'] == 6) {
			return true;
		}
	} else {
		global $_user;
		if (!isset($_user)) {
			//in some cases, api_set_anonymous doesn't seem to be
			//triggered in local.inc.php. Make sure it is.	
			//Occurs in agenda for admin links - YW
			global $use_anonymous;
                        if (isset($use_anonymous) && $use_anonymous == true) {
                                api_set_anonymous();
                        }
			return true;
		}
		if (isset($_user['is_anonymous']) and $_user['is_anonymous'] === true) {
			return true;
		}
	}
	return false;
}

/**
 * Displays message "You are not allowed here..." and exits the entire script.
 * @param	bool	Whether or not to print headers (default = false -> does not print them)
 *
 * @author Roan Embrechts
 * @author Yannick Warnier
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*
 * @version 1.0, February 2004
 * @version dokeos 1.8, August 2006
*/
function api_not_allowed($print_headers = false) {
	$home_url = api_get_path(WEB_PATH);
	$user = api_get_user_id();
	$course = api_get_course_id();
	
	$origin = isset($_GET['origin'])?$_GET['origin']:'';
	
	if ($origin == 'learnpath') {
		
		echo '
				<style type="text/css" media="screen, projection">
				/*<![CDATA[*/
				@import "'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css";
				/*]]>*/
				</style>';
	}		
		
	if ((isset($user) && !api_is_anonymous()) 
		&& (!isset($course) || $course==-1) 
		&& empty($_GET['cidReq']))
	{//if the access is not authorized and there is some login information 
	 // but the cidReq is not found, assume we are missing course data and send the user
	 // to the user_portal			
	 	if ((!headers_sent() or $print_headers) && $origin != 'learnpath'){Display::display_header('');}
		echo '<div align="center">';
		Display::display_error_message(get_lang('NotAllowedClickBack').'<br/><br/><a href="'.$_SERVER['HTTP_REFERER'].'">'.get_lang('BackToPreviousPage').'</a><br/>',false);
		echo '</div>';
		if ($print_headers && $origin != 'learnpath'){Display::display_footer();}
		die();
	} elseif (!empty($_SERVER['REQUEST_URI']) && !empty($_GET['cidReq'])) {
		//only display form and return to the previous URL if there was a course ID included
		if (!empty($user) && !api_is_anonymous()) {
			if ((!headers_sent() or $print_headers) && $origin != 'learnpath') { Display::display_header('');}
			echo '<div align="center">';
			Display::display_error_message(get_lang('NotAllowedClickBack').'<br/><br/><a href="'.$_SERVER['HTTP_REFERER'].'">'.get_lang('BackToPreviousPage').'</a><br/>',false);
			echo '</div>';
			if ($print_headers && $origin != 'learnpath') {Display::display_footer();}
			die();			
		} else {
			include_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
			$form = new FormValidator('formLogin','post',api_get_self().'?'.$_SERVER['QUERY_STRING']);
			$form->addElement('static',null,null,'Username');
			$form->addElement('text','login','',array('size'=>15));
			$form->addElement('static',null,null,'Password');
			$form->addElement('password','password','',array('size'=>15));
			$form->addElement('style_submit_button','submitAuth',get_lang('Enter'),'class="login"');
			$test ='<div id="expire_session"><br/>'.$form->return_form();'</div>';
			
			if((!headers_sent() or $print_headers) && $origin != 'learnpath'){Display::display_header('');}
			Display::display_error_message('<left>'.get_lang('NotAllowed').'<br/>'.get_lang('PleaseLoginAgainFromFormBelow').'<br/>'.$test.'</left>',false);			
			$_SESSION['request_uri'] = $_SERVER['REQUEST_URI'];
			if ($print_headers && $origin != 'learnpath') {Display::display_footer();}
			die();
		}
	} else {
		if (!empty($user) && !api_is_anonymous()) {						
			if ((!headers_sent() or $print_headers) && $origin != 'learnpath') {Display::display_header('');}												
			echo '<div align="center">';
			Display::display_error_message(get_lang('NotAllowedClickBack').'<br/><br/><a href="'.$_SERVER['HTTP_REFERER'].'">'.get_lang('BackToPreviousPage').'</a><br/>',false);
			echo '</div>';
			if ($print_headers && $origin != 'learnpath') {Display::display_footer();}
			die();			
		} else {
			//if no course ID was included in the requested URL, redirect to homepage
			if ($print_headers && $origin != 'learnpath') {Display::display_header('');}
			echo '<div align="center">';
			Display::display_error_message(get_lang('NotAllowed').'<br/><br/><a href="'.$home_url.'">'.get_lang('PleaseLoginAgainFromHomepage').'</a><br/>',false);
			echo '</div>';
			if ($print_headers && $origin != 'learnpath') {Display::display_footer();}
			die();
		}
	}
}

/*
==============================================================================
		WHAT'S NEW
		functions for the what's new icons
		in the user course list
==============================================================================
*/
/**
 * Gets a UNIX timestamp from a MySQL datetime format string
 * @param $last_post_datetime standard output date in a sql query
 * @return unix timestamp
 * @author Toon Van Hoecke <Toon.VanHoecke@UGent.be>
 * @version October 2003
 * @desc convert sql date to unix timestamp
*/
function convert_mysql_date($last_post_datetime) {
	list ($last_post_date, $last_post_time) = split(" ", $last_post_datetime);
	list ($year, $month, $day) = explode("-", $last_post_date);
	list ($hour, $min, $sec) = explode(":", $last_post_time);
	$announceDate = mktime((int)$hour, (int)$min, (int)$sec, (int)$month, (int)$day, (int)$year);
	return $announceDate;
}
/**
 * Gets a MySQL datetime format string from a UNIX timestamp
 * @param   int     UNIX timestamp, as generated by the time() function. Will be generated if parameter not provided
 * @return  string  MySQL datetime format, like '2009-01-30 12:23:34'
 */
function api_get_datetime($time=null) {
	if (!isset($time)) { $time = time();}
    return date('Y-m-d H:i:s', $time);
}

/**
 * Gets item visibility from the item_property table
 * @param	array	Course properties array (result of api_get_course_info())
 * @param	string	Tool (learnpath, document, etc)
 * @param	int		The item ID in the given tool
 * @return	int		-1 on error, 0 if invisible, 1 if visible 
 */
function api_get_item_visibility($_course,$tool,$id) {
	if (!is_array($_course) or count($_course)==0 or empty($tool) or empty($id)) return -1;
	$tool = Database::escape_string($tool);
	$id = Database::escape_string($id);
	$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY,$_course['dbName']);
	$sql = "SELECT * FROM $TABLE_ITEMPROPERTY WHERE tool = '$tool' AND ref = $id";
	$res = api_sql_query($sql);
	if($res === false or Database::num_rows($res)==0) return -1;
	$row = Database::fetch_array($res);
	return $row['visibility'];  	
}

/**
 * Updates or adds item properties to the Item_propetry table
 * Tool and lastedit_type are language independant strings (langvars->get_lang!)
 *
 * @param $_course : array with course properties
 * @param $tool : tool id, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param $item_id : id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 * @param $lastedit_type : add or update action (1) message to be translated (in trad4all) : e.g. DocumentAdded, DocumentUpdated;
 * 												(2) "delete"; (3) "visible"; (4) "invisible";
 * @param $user_id : id of the editing/adding user
 * @param $to_group_id : id of the intended group ( 0 = for everybody), only relevant for $type (1)
 * @param $to_user_id : id of the intended user (always has priority over $to_group_id !), only relevant for $type (1)
 * @param string $start_visible 0000-00-00 00:00:00 format
 * @param unknown_type $end_visible 0000-00-00 00:00:00 format
 * @return boolean False if update fails.
 * @author Toon Van Hoecke <Toon.VanHoecke@UGent.be>, Ghent University
 * @version January 2005
 * @desc update the item_properties table (if entry not exists, insert) of the course
 */
function api_item_property_update($_course, $tool, $item_id, $lastedit_type, $user_id, $to_group_id = 0, $to_user_id = NULL, $start_visible = 0, $end_visible = 0) {
	$tool = Database::escape_string($tool);
	$item_id = Database::escape_string($item_id);
	$lastedit_type = Database::escape_string($lastedit_type);
	$user_id = Database::escape_string($user_id);
	$to_group_id = Database::escape_string($to_group_id);
	$to_user_id = Database::escape_string($to_user_id);
	$start_visible = Database::escape_string($start_visible);
	$end_visible = Database::escape_string($end_visible);
	$to_filter = "";
	$time = time();
	$time = date("Y-m-d H:i:s", $time);
	$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY,$_course['dbName']);
	if ($to_user_id <= 0) {
		$to_user_id = NULL;//no to_user_id set
	} 
	$start_visible = ($start_visible == 0) ? "0000-00-00 00:00:00" : $start_visible;
	$end_visible = ($end_visible == 0) ? "0000-00-00 00:00:00" : $end_visible;
	// set filters for $to_user_id and $to_group_id, with priority for $to_user_id
	$filter = "tool='$tool' AND ref='$item_id'";
	if ($item_id == "*") {
			$filter = "tool='$tool' AND visibility<>'2'"; // for all (not deleted) items of the tool
	}
	// check if $to_user_id and $to_group_id are passed in the function call
	// if both are not passed (both are null) then it is a message for everybody and $to_group_id should be 0 !
	if (is_null($to_user_id) && is_null($to_group_id)) {
		$to_group_id = 0;
	}
	if (!is_null($to_user_id)) {
		$to_filter = " AND to_user_id='$to_user_id'"; // set filter to intended user
	} else {
		if (($to_group_id != 0) && $to_group_id == strval(intval($to_group_id))) {
			$to_filter = " AND to_group_id='$to_group_id'"; // set filter to intended group
		}
	}
	// update if possible
	$set_type = "";
	switch ($lastedit_type) {
		case "delete" : // delete = make item only visible for the platform admin
			$visibility = '2';
			$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
										WHERE $filter";
			break;
		case "visible" : // change item to visible
			$visibility = '1';
			$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
										WHERE $filter";
			break;
		case "invisible" : // change item to invisible
			$visibility = '0';
			$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
										WHERE $filter";
			break;
		default : // item will be added or updated
			$set_type = ", lastedit_type='$lastedit_type' ";
			$visibility = '1';
			$filter .= $to_filter;
			$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_date='$time', lastedit_user_id='$user_id' $set_type
										WHERE $filter";
	}

	$res = mysql_query($sql);
	// insert if no entries are found (can only happen in case of $lastedit_type switch is 'default')
	if (mysql_affected_rows() == 0) {
		if (!is_null($to_user_id)) {
			// $to_user_id has more priority than $to_group_id
			$to_field = "to_user_id";
			$to_value = $to_user_id;
		} else {
			// $to_user_id is not set	
			$to_field = "to_group_id";
			$to_value = $to_group_id;
		}
		$sql = "INSERT INTO $TABLE_ITEMPROPERTY
						   		  			(tool,   ref,       insert_date,insert_user_id,lastedit_date,lastedit_type,   lastedit_user_id,$to_field,  visibility,   start_visible,   end_visible)
						         	VALUES 	('$tool','$item_id','$time',    '$user_id',	   '$time',		 '$lastedit_type','$user_id',	   '$to_value','$visibility','$start_visible','$end_visible')";
		$res = mysql_query($sql);
		if (!$res) {
			return FALSE;
		}
	}
	return TRUE;
}

/*
==============================================================================
		Language Dropdown
==============================================================================
*/
/**
*	Displays a combobox so the user can select his/her preferred language.
*   @param string The desired name= value for the select
*   @return string
*/

function api_get_languages_combo($name="language") {
    $ret = "";

	$platformLanguage = api_get_setting('platformLanguage');
    
    /* retrieve a complete list of all the languages. */
	$language_list = api_get_languages();

    if (count($language_list['name']) < 2) {
    	return $ret; 
    }

	/* the the current language of the user so that his/her language occurs as
     * selected in the dropdown menu */
	if(isset($_SESSION['user_language_choice']))
		$default = $_SESSION['user_language_choice'];
    else
		$default = $platformLanguage;
	
    $languages = $language_list['name'];
	$folder = $language_list['folder']; 
    
    $ret .= '<select name="'.$name.'">';
	foreach ($languages as $key => $value) {
		if ($folder[$key] == $default)
            $selected = ' selected="selected"';
        else
            $selected = '';

        $ret .= sprintf('<option value=%s" %s>%s</option>'."\n",
                    $folder[$key], $selected, $value);
	}
	$ret .= '</select>';

    return $ret;
}

/**
*	Displays a form (drop down menu) so the user can select his/her preferred language.
*	The form works with or without javascript
*   @param  boolean Hide form if only one language available (defaults to false = show the box anyway)
*   @return void Display the box directly
*/
function api_display_language_form($hide_if_no_choice=false) {
	$platformLanguage = api_get_setting('platformLanguage');
	$dirname = api_get_path(SYS_PATH)."main/lang/"; // this line is probably no longer needed
	// retrieve a complete list of all the languages.
	$language_list = api_get_languages();
    if (count($language_list['name'])<=1 && $hide_if_no_choice == true) {
    	return; //don't show any form
    }
	// the the current language of the user so that his/her language occurs as selected in the dropdown menu
	if(isset($_SESSION['user_language_choice']))
	{
		$user_selected_language = $_SESSION['user_language_choice'];
	}
	if (!isset ($user_selected_language))
		$user_selected_language = $platformLanguage;
	$original_languages = $language_list['name'];
	$folder = $language_list['folder']; // this line is probably no longer needed
?>
	<script language="JavaScript" type="text/JavaScript">
	<!--
	function jumpMenu(targ,selObj,restore){ //v3.0
	  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
	  if (restore) selObj.selectedIndex=0;
	}
	//-->
	</script>
	<?php


	echo "<form id=\"lang_form\" name=\"lang_form\" method=\"post\" action=\"".api_get_self()."\">", "<select name=\"language_list\"  onchange=\"jumpMenu('parent',this,0)\">";
	foreach ($original_languages as $key => $value)
	{
		if ($folder[$key] == $user_selected_language)
			$option_end = " selected=\"selected\" >";
		else
			$option_end = ">";
		echo "<option value=\"".api_get_self()."?language=".$folder[$key]."\"$option_end";
		#echo substr($value,0,16); #cut string to keep 800x600 aspect
		echo $value;
		echo "</option>\n";
	}
	echo "</select>";
	echo "<noscript><input type=\"submit\" name=\"user_select_language\" value=\"".get_lang("Ok")."\" /></noscript>";
	echo "</form>";
}
/**
* Return a list of all the languages that are made available by the admin.
* @return array An array with all languages. Structure of the array is
*  array['name'] = An array with the name of every language
*  array['folder'] = An array with the corresponding dokeos-folder
*/
function api_get_languages() {
	$tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
	$sql = "SELECT * FROM $tbl_language WHERE available='1' ORDER BY original_name ASC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_array($result)) {
		$language_list['name'][] = $row['original_name'];
		$language_list['folder'][] = $row['dokeos_folder'];
	}
	return $language_list;
}
/**
 * Gets language isocode column from the language table, taking the current language as a query parameter.
 * Returned codes are according to the following standards (in order of preference):
 * -  ISO 639-1 : Alpha-2 code (two-letters code - en, fr, es, ...)
 * -  RFC 4646  : five-letter code based on the ISO 639 two-letter language codes
 *    and the ISO 3166 two-letter territory codes (pt-BR, ...)
 * -  ISO 639-2 : Alpha-3 code (three-letters code - ast, fur, ...)
 * @return	string	The isocode or null if error
 */
function api_get_language_isocode() {
	$tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
	$sql = "SELECT isocode FROM $tbl_language WHERE dokeos_folder = '".api_get_interface_language()."'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if(mysql_num_rows($res)) {
		$row = mysql_fetch_array($res);
		return $row['isocode'];
	}
	return null;
}
/**
 * Returns a list of CSS themes currently available in the CSS folder
 * @return	array	List of themes directories from the css folder
 */
function api_get_themes() {
	$cssdir = api_get_path(SYS_PATH).'main/css/';
	$list_dir= array();
	$list_name= array();
	
	if (@is_dir($cssdir)) {
		$themes = @scandir($cssdir);
		
		if (is_array($themes)) {
			if($themes !== false) {
				sort($themes);
				
				foreach($themes as $theme) {
					if(substr($theme,0,1)=='.') {
						//ignore
						continue;
					} else {
						if(@is_dir($cssdir.$theme)) {
							$list_dir[] = $theme;
							$list_name[] = ucwords(str_replace('_',' ',$theme));
						}	
					}
				}
			}
		}		
	}
	$return=array();
	$return[]=$list_dir;
	$return[]=$list_name;	
	return $return;
}

/*
==============================================================================
		WYSIWYG HTML AREA
		functions for the WYSIWYG html editor, TeX parsing...
==============================================================================
*/
/**
* Displays the FckEditor WYSIWYG editor for online editing of html
* @param string $name The name of the form-element
* @param string $content The default content of the html-editor
* @param int $height The height of the form element
* @param int $width The width of the form element
* @param string $optAttrib optional attributes for the form element
*/
function api_disp_html_area($name, $content = '', $height = '', $width = '100%', $optAttrib = '') {
	global $_configuration, $_course, $fck_attribute;
	require_once(dirname(__FILE__).'/formvalidator/Element/html_editor.php');
	$editor = new HTML_QuickForm_html_editor($name);
	$editor->setValue($content);
	if( $height != '') {
		$fck_attribute['Height'] = $height;
	}
	if( $width != '') {
		$fck_attribute['Width'] = $width;
	}
	echo $editor->toHtml();
}
function api_return_html_area($name, $content = '', $height = '', $width = '100%', $optAttrib = '') {
	global $_configuration, $_course, $fck_attribute;
	require_once(dirname(__FILE__).'/formvalidator/Element/html_editor.php');
	$editor = new HTML_QuickForm_html_editor($name);
	$editor->setValue($content);
	if( $height != '') {
		$fck_attribute['Height'] = $height;
	}
	if( $width != '') {
		$fck_attribute['Width'] = $width;
	}
	return $editor->toHtml();
}

/**
 * Send an email.
 *
 * Wrapper function for the standard php mail() function. Change this function
 * to your needs. The parameters must follow the same rules as the standard php
 * mail() function. Please look at the documentation on http: //www. php.
 * net/manual/en/function. mail.php
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $additional_headers
 * @param string $additional_parameters
 */
function api_send_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null) {
	return mail($to, $subject, $message, $additional_headers, $additional_parameters);
}


/**
 * Find the largest sort value in a given user_course_category
 * This function is used when we are moving a course to a different category
 * and also when a user subscribes to a courses (the new courses is added to the end
 * of the main category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $user_course_category: the id of the user_course_category
 * @return int the value of the highest sort of the user_course_category
*/
function api_max_sort_value($user_course_category, $user_id) {

	$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	$sql_max = "SELECT max(sort) as max_sort FROM $tbl_course_user WHERE user_id='".$user_id."' AND user_course_cat='".$user_course_category."'";
	$result_max = mysql_query($sql_max) or die(mysql_error());
	if (mysql_num_rows($result_max) == 1) {
		$row_max = mysql_fetch_array($result_max);
		$max_sort = $row_max['max_sort'];
	} else {
		$max_sort = 0;
	}

	return $max_sort;
}

/**
 * This function converts the string "true" or "false" to a boolean true or false.
 * This function is in the first place written for the Dokeos Config Settings (also named AWACS)
 * @param string "true" or "false"
 * @return boolean true or false
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function string_2_boolean($string) {
	if ($string == "true") {
		return true;
	}
	if ($string == "false") {
		return false;
	}
}

/**
 * Determines the number of plugins installed for a given location
 */
function api_number_of_plugins($location) {
	global $_plugins;
	if (isset($_plugins[$location]) && is_array($_plugins[$location])) {
		return count($_plugins[$location]);
	}
	return 0;
}

/**
 * including the necessary plugins
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function api_plugin($location) {
	global $_plugins;

	if (isset($_plugins[$location]) && is_array($_plugins[$location])) {
		foreach ($_plugins[$location] as $this_plugin) {
			include (api_get_path(SYS_PLUGIN_PATH)."$this_plugin/index.php");
		}
	}
}

/**
* Checks to see wether a certain plugin is installed.
* @return boolean true if the plugin is installed, false otherwise.
*/
function api_is_plugin_installed($plugin_list, $plugin_name) {
	foreach ($plugin_list as $plugin_location) {
		if ( array_search($plugin_name, $plugin_location) !== false ) return true;
	}
	return false;
}

/**
 * Apply parsing to content to parse tex commandos that are seperated by [tex]
 * [/tex] to make it readable for techexplorer plugin.
 * @param string $text The text to parse
 * @return string The text after parsing.
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version June 2004
*/
function api_parse_tex($textext) {
	if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
		$textext = str_replace(array ("[tex]", "[/tex]"), array ("<object classid=\"clsid:5AFAB315-AD87-11D3-98BB-002035EFB1A4\"><param name=\"autosize\" value=\"true\" /><param name=\"DataType\" value=\"0\" /><param name=\"Data\" value=\"", "\" /></object>"), $textext);
	} else {
		$textext = str_replace(array ("[tex]", "[/tex]"), array ("<embed type=\"application/x-techexplorer\" texdata=\"", "\" autosize=\"true\" pluginspage=\"http://www.integretechpub.com/techexplorer/\">"), $textext);
	}
	return $textext;
}


/**
 * Transform a number of seconds in hh:mm:ss format
 * @author Julian Prud'homme
 * @param integer the number of seconds
 * @return string the formated time
 */
function api_time_to_hms($seconds) {
  
  //if seconds = -1, it means we have wrong datas in the db
  if($seconds==-1) {
  	  return get_lang('Unknown').Display::return_icon('info2.gif',get_lang('WrongDatasForTimeSpentOnThePlatform'));
  }
  
  //How many hours ?
  $hours = floor($seconds / 3600);

  //How many minutes ?
  $min = floor(($seconds - ($hours * 3600)) / 60);

  //How many seconds
  $sec = floor($seconds - ($hours * 3600) - ($min * 60));

  if ($sec < 10)
    $sec = "0".$sec;

  if ($min < 10)
    $min = "0".$min;

  return $hours.":".$min.":".$sec;

}


/**
 * function adapted from a php.net comment
 * copy recursively a folder
 * @param the source folder
 * @param the dest folder
 * @param an array of excluded file_name (without extension)
 * @param copied_files the returned array of copied files
 */

function copyr($source, $dest, $exclude=array(), $copied_files=array()) {
    // Simple copy for a file
    if (is_file($source)) {
    	$path_infos = pathinfo($source);
    	if(!in_array($path_infos['filename'], $exclude))
       		copy($source, $dest);
       	return;
    }

 
    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }
 
    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }
 
        // Deep copy directories
        if ($dest !== "$source/$entry") {
            $zip_files = copyr("$source/$entry", "$dest/$entry", $exclude, $copied_files);
        }
    }
    // Clean up
    $dir->close();
    return $zip_files;
}


function api_chmod_R($path, $filemode) { 
    if (!is_dir($path))
       return chmod($path, $filemode);

    $dh = opendir($path);
    while ($file = readdir($dh)) {
        if ($file != '.' && $file != '..') {
            $fullpath = $path.'/'.$file;
            if (!is_dir($fullpath)) {
              if (!chmod($fullpath, $filemode))
                 return FALSE;
            } else {
              if (!api_chmod_R($fullpath, $filemode))
                 return FALSE;
            }
        }
    }
 
    closedir($dh);
    
    if(chmod($path, $filemode))
      return TRUE;
    else 
      return FALSE;
}

/**
 * Get Dokeos version from the configuration files
 * @return	string	A string of type "1.8.4", or an empty string if the version could not be found
 */
function api_get_version() {
	global $_configuration;
	if(!empty($_configuration['dokeos_version'])) {
		return $_configuration['dokeos_version'];
	} else {
		return '';
	}
}


/**
 * Check if status given in parameter exists in the platform
 * @param mixed the status (can be either int either string)
 * @return true if the status exists, else returns false
 */
function api_status_exists($status_asked) {
	global $_status_list;
	if (in_array($status_asked, $_status_list)) {
		return true;
	} else  {
		return isset($_status_list[$status_asked]);
	}
}

/**
 * Check if status given in parameter exists in the platform
 * @param mixed the status (can be either int either string)
 * @return true if the status exists, else returns false
 */
function api_status_key($status) {
	global $_status_list;
	if (isset($_status_list[$status])) {
		return $status;
	} else {
		return array_search($status,$_status_list);
	}
}

/**
 * get the status langvars list
 * @return array the list of status with their translations
 */
function api_get_status_langvars() {
	return array(
				COURSEMANAGER=>get_lang('Teacher'),
				SESSIONADMIN=>get_lang('SessionsAdmin'),
				DRH=>get_lang('Drh'),
				STUDENT=>get_lang('Student'),
				ANONYMOUS=>get_lang('Anonymous')
				);
}
/**
 * Sets a platform configuration setting to a given value
 * @param	string	The variable we want to update
 * @param	string	The value we want to record
 * @param	string	The sub-variable if any (in most cases, this will remain null)
 * @param	string	The category if any (in most cases, this will remain null)
 * @param	int		The access_url for which this parameter is valid
 */
function api_set_setting($var,$value,$subvar=null,$cat=null,$access_url=1) {
	if(empty($var)) { return false; }
	$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$var = Database::escape_string($var);
	$value = Database::escape_string($value);
	$access_url = (int) $access_url;
	if(empty($access_url)){$access_url=1;}
	$select = "SELECT * FROM $t_settings WHERE variable = '$var' ";
	if(!empty($subvar)) {
		$subvar = Database::escape_string($subvar);
		$select .= " AND subkey = '$subvar'";
	}
	if(!empty($cat)) {
		$cat = Database::escape_string($cat);
		$select .= " AND category = '$cat'";
	}
	if($access_url > 1) {
		$select .= " AND access_url = $access_url";
	} else {
		$select .= " AND access_url = 1 ";
	}	

	$res = api_sql_query($select,__FILE__,__LINE__);
	if(Database::num_rows($res)>0) {   
		//found item for this access_url
		$row = Database::fetch_array($res);
		$update = "UPDATE $t_settings SET selected_value = '$value' WHERE id = ".$row['id'] ;
		$res = api_sql_query($update,__FILE__,__LINE__);
	} else { 
		//Item not found for this access_url, we have to check if it exist with access_url = 1
		$select = "SELECT * FROM $t_settings WHERE variable = '$var' AND access_url = 1 ";
		// just in case 
		if ($access_url==1) {
			if (!empty($subvar)) {
				$select .= " AND subkey = '$subvar'";
			}
			if (!empty($cat)) {
				$select .= " AND category = '$cat'";
			}
			$res = api_sql_query($select,__FILE__,__LINE__);
			
			if (Database::num_rows($res)>0) { //we have a setting for access_url 1, but none for the current one, so create one
				$row = Database::fetch_array($res);
				$insert = "INSERT INTO $t_settings " .
						"(variable,subkey," .
						"type,category," .
						"selected_value,title," .
						"comment,scope," .
						"subkeytext,access_url)" .
						" VALUES " .
						"('".$row['variable']."',".(!empty($row['subkey'])?"'".$row['subkey']."'":"NULL")."," .
						"'".$row['type']."','".$row['category']."'," .
						"'$value','".$row['title']."'," .
						"".(!empty($row['comment'])?"'".$row['comment']."'":"NULL").",".(!empty($row['scope'])?"'".$row['scope']."'":"NULL")."," .
						"".(!empty($row['subkeytext'])?"'".$row['subkeytext']."'":"NULL").",$access_url)";
				$res = api_sql_query($insert,__FILE__,__LINE__);
			} else { // this setting does not exist
				error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all',0);
			}
		} else {	
			// other access url 
			if (!empty($subvar)) {
				$select .= " AND subkey = '$subvar'";
			}
			if (!empty($cat)) {
				$select .= " AND category = '$cat'";
			}
			$res = api_sql_query($select,__FILE__,__LINE__);
						
			if (Database::num_rows($res)>0) { //we have a setting for access_url 1, but none for the current one, so create one				
				$row = Database::fetch_array($res);
				if ($row['access_url_changeable']==1) {
					$insert = "INSERT INTO $t_settings " .
							"(variable,subkey," . 
							"type,category," .
							"selected_value,title," .
							"comment,scope," .
							"subkeytext,access_url, access_url_changeable)" .
							" VALUES " .
							"('".$row['variable']."',".
							(!empty($row['subkey'])?"'".$row['subkey']."'":"NULL")."," .
							"'".$row['type']."','".$row['category']."'," .
							"'$value','".$row['title']."'," .
							"".(!empty($row['comment'])?"'".$row['comment']."'":"NULL").",".
							(!empty($row['scope'])?"'".$row['scope']."'":"NULL")."," .
							"".(!empty($row['subkeytext'])?"'".$row['subkeytext']."'":"NULL").",$access_url,".$row['access_url_changeable'].")";
					$res = api_sql_query($insert,__FILE__,__LINE__);
				}
			} else { // this setting does not exist
				error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all. The access_url is: '.$access_url.' ',0);
			}			
		}	
	}
}
/**
 * Sets a whole category of settings to one specific value
 * @param	string	Category
 * @param	string 	Value
 * @param	int		Access URL. Optional. Defaults to 1
 */
function api_set_settings_category($category,$value=null,$access_url=1) {
	if (empty($category)){return false;}
	$category = Database::escape_string($category);
	$t_s = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$access_url = (int) $access_url;
	if (empty($access_url)){$access_url=1;}
	if (isset($value)) {
		$value = Database::escape_string($value);
		$sql = "UPDATE $t_s SET selected_value = '$value' WHERE category = '$category' AND access_url = $access_url";
		$res = api_sql_query($sql,__FILE__,__LINE__); 
		if($res === false){ return false; }
		return true;
	} else {
		$sql = "UPDATE $t_s SET selected_value = NULL WHERE category = '$category' AND access_url = $access_url";
		$res = api_sql_query($sql,__FILE__,__LINE__); 
		if($res === false){ return false; }
		return true;		
	}
}
/**
 * Get all available access urls in an array (as in the database)
 * @return	array	Array of database records
 */
function api_get_access_urls($from=0,$to=1000000,$order='url',$direction='ASC') {
	$result = array();
	$t_au = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
	$from = (int) $from;
	$to = (int) $to;
	$order = Database::escape_string($order);
	$direction = Database::escape_string($direction);
	$sql = "SELECT id, url, description, active, created_by, tms FROM $t_au ORDER BY $order $direction LIMIT $to OFFSET $from";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if ($res !==false) {
		$result = api_store_result($res);
	}
	return $result;
}

/**
 * Get the access url info in an array
 * @param 	id of the access url 
 * @return	array Array with all the info (url, description, active, created_by, tms) from the access_url table 
 * @author 	Julio Montoya Armas
 */
function api_get_access_url($id) {
	global $_configuration;
	$result = array();
	// calling the Database:: library dont work this is handmade
	//$table_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
	$table='access_url';
	$database = $_configuration['main_database'];
	$table_access_url =  "`".$database."`.`".$table."`";	
	$sql = "SELECT url, description, active, created_by, tms FROM $table_access_url WHERE id = '$id' ";	
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$result = @mysql_fetch_array($res);
	return $result;
}

/**
 * Adds an access URL into the database
 * @param	string	URL
 * @param	string	Description
 * @param	int		Active (1= active, 0=disabled)
 * @return	int		The new database id, or the existing database id if this url already exists
 */
function api_add_access_url($u,$d='',$a=1) {
	$t_au = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
	$u = Database::escape_string($u);
	$d = Database::escape_string($d);
	$a = (int) $a;
	$sql = "SELECT * FROM $t_au WHERE url LIKE '$u'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if($res === false) {
		//problem querying the database - return false
		return false;
	} else {
		if(Database::num_rows($res)>0) {
			return Database::result($res,0,'id');
		} else {
			$ui = api_get_user_id();
			$time = 
			$sql = "INSERT INTO $t_au (url,description,active,created_by,tms)"
					." VALUES ('$u','$d',$a,$ui,'')";
			$res = api_sql_query($sql,__FILE__,__LINE__);
			if($res === false){return false;}
			return Database::insert_id();
		}
	}
}
/**
 * Gets all the current settings for a specific access url
 * @param	string	The category, if any, that we want to get
 * @param	string	Whether we want a simple list (display a catgeory) or a grouped list (group by variable as in settings.php default). Values: 'list' or 'group'
 * @param	int		Access URL's ID. Optional. Uses 1 by default, which is the unique URL
 * @return	array	Array of database results for the current settings of the current access URL
 */
function api_get_settings($cat=null,$ordering='list',$access_url=1,$url_changeable=0) {
	$results = array();
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$access_url = (int) $access_url;
	
	$url_changeable_where='';
	if ($url_changeable==1) {
		$url_changeable_where= " AND access_url_changeable= '1' "; 
	}	
	if (empty($access_url)) {$access_url=1;}
	$sql = "SELECT id, variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable " .
			" FROM $t_cs WHERE access_url = $access_url  $url_changeable_where ";
	if(!empty($cat)) {
		$cat = Database::escape_string($cat);
		$sql .= " AND category='$cat' ";
	}
	if($ordering=='group') {
		$sql .= " GROUP BY variable ORDER BY id ASC";
	} else {
		$sql .= " ORDER BY 1,2 ASC";
	} 
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if ($res === false) {return $results;}
	$results = api_store_result($res);
	return $results;
}
/**
 * Gets the distinct settings categories
 * @param	array	Array of strings giving the categories we want to exclude
 * @param	int		Access URL. Optional. Defaults to 1
 * @return	array	A list of categories
 */
function api_get_settings_categories($exceptions=array(),$access_url=1) {
	$result = array();
	$access_url = (int) $access_url;
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$list = "'".implode("','",$exceptions)."'";
	$sql = "SELECT DISTINCT category FROM $t_cs";
	if ($list != "'',''" and $list != "''" and !empty($list)) {
		$sql .= " WHERE category NOT IN ($list)"; 
	}
	$r = api_sql_query($sql,__FILE__,__LINE__);
	if ($r === false) {
		return $result;
	}
	$result = api_store_result($r);
	return $result;
}
/**
 * Delete setting
 * @param	string	Variable
 * @param	string	Subkey
 * @param	int		Access URL
 * @return	boolean	False on failure, true on success
 */
function api_delete_setting($v,$s=NULL,$a=1) {
	if (empty($v)) {return false;}
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$v = Database::escape_string($v);
	$a = (int) $a;
	if (empty($a)){$a=1;}
	if (!empty($s)) {
		$s = Database::escape_string($s);
		$sql = "DELETE FROM $t_cs WHERE variable = '$v' AND subkey = '$s' AND access_url = $a";
		$r = api_sql_query($sql);
		return $r;
	} else {
		$sql = "DELETE FROM $t_cs WHERE variable = '$v' AND access_url = $a";
		$r = api_sql_query($sql);
		return $r;
	}
}
/**
 * Delete all the settings from one category
 * @param	string	Category
 * @param	int		Access URL
 * @return	boolean	False on failure, true on success
 */
function api_delete_category_settings($c,$a=1) {
	if (empty($c)){return false;}
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$c = Database::escape_string($c);
	$a = (int) $a;
	if(empty($a)){$a=1;}
	$sql = "DELETE FROM $t_cs WHERE category = '$c' AND access_url = $a";
	$r = api_sql_query($sql);
	return $r;
}
/**
 * Sets a platform configuration setting to a given value
 * @param	string	The value we want to record
 * @param	string	The variable name we want to insert
 * @param	string	The subkey for the variable we want to insert
 * @param	string	The type for the variable we want to insert
 * @param	string	The category for the variable we want to insert
 * @param	string	The title
 * @param	string	The comment
 * @param	string	The scope
 * @param	string	The subkey text
 * @param	int		The access_url for which this parameter is valid
 * @param	int		The changeability of this setting for non-master urls
 * @return	boolean	true on success, false on failure
 */
function api_add_setting($val,$var,$sk=null,$type='textfield',$c=null,$title='',$com='',$sc=null,$skt=null,$a=1,$v=0) {
	if (empty($var) or !isset($val)) { return false; }
	$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$var = Database::escape_string($var);
	$val = Database::escape_string($val);
	$a = (int) $a;
	if (empty($a)){$a=1;}
	//check if this variable doesn't exist already
	$select = "SELECT * FROM $t_settings WHERE variable = '$var' ";
	if (!empty($sk)) {
		$sk = Database::escape_string($sk);
		$select .= " AND subkey = '$sk'";
	}
	if ($a > 1) {
		$select .= " AND access_url = $a";
	} else {
		$select .= " AND access_url = 1 ";
	}
	$res = api_sql_query($select,__FILE__,__LINE__);
	if(Database::num_rows($res)>0) { //found item for this access_url
		$row = Database::fetch_array($res);
		return $row['id']; 	
	} else { //item not found for this access_url, we have to check if the whole thing is missing 
	  //(in which case we ignore the insert) or if there *is* a record but just for access_url=1		
		$insert = "INSERT INTO $t_settings " .
				"(variable,selected_value," .
				"type,category," .
				"subkey,title," .
				"comment,scope," .
				"subkeytext,access_url,access_url_changeable)" .
				" VALUES ('$var','$val',";
		if (isset($type)) {
			$type = Database::escape_string($type);
			$insert .= "'$type',";
		} else {
			$insert .= "NULL,";
		}
		if(isset($c)) {//category
			$c = Database::escape_string($c);
			$insert .= "'$c',";
		} else {
			$insert .= "NULL,";
		}
		if (isset($sk)) { //subkey
			$sk = Database::escape_string($sk);
			$insert .= "'$sk',";
		} else {
			$insert .= "NULL,";
		}
		if(isset($title)) {//title
			$title = Database::escape_string($title);
			$insert .= "'$title',";
		} else {
			$insert .= "NULL,";
		}
		if (isset($com)) {//comment
			$com = Database::escape_string($com);
			$insert .= "'$com',";
		} else {
			$insert .= "NULL,";
		}
		if(isset($sc)) {//scope
			$sc = Database::escape_string($sc);
			$insert .= "'$sc',";
		} else {
			$insert .= "NULL,";
		}
		if (isset($skt)) {//subkey text
			$skt = Database::escape_string($skt);
			$insert .= "'$skt',";
		} else {
			$insert .= "NULL,";
		}
		$insert .= "$a,$v)";
		$res = api_sql_query($insert,__FILE__,__LINE__);
		return $res;
	}
}
/**
 * Returns wether a user can or can't view the contents of a course.
 * 
 * @param   int $userid     User id or NULL to get it from $_SESSION 
 * @param   int $cid        Course id to check whether the user is allowed.
 * @return  bool
 */
function api_is_course_visible_for_user( $userid = null, $cid = null ) {
    if ( $userid == null ) {
        $userid = $_SESSION['_user']['user_id'];
    }
    if( empty ($userid) or strval(intval($userid)) != $userid ) {
        if ( api_is_anonymous() ) {
        	$userid = api_get_anonymous_id();
        } else {
            return false;
        }
    }
    $cid = Database::escape_string($cid);
    global $is_platformAdmin;

    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
    $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);

    $sql = "SELECT 
                $course_table.category_code,
                $course_table.visibility,
                $course_table.code,
                $course_cat_table.code
            FROM $course_table 
            LEFT JOIN $course_cat_table 
                ON $course_table.category_code = $course_cat_table.code
            WHERE 
                $course_table.code = '$cid'
            LIMIT 1";

    $result = api_sql_query($sql, __FILE__, __LINE__);

    if (Database::num_rows($result) > 0) {
        $visibility = Database::fetch_array($result);
        $visibility = $visibility['visibility'];
    } else {
        $visibility = 0;
    }
    //shortcut permissions in case the visibility is "open to the world"
    if ($visibility === COURSE_VISIBILITY_OPEN_WORLD) {
        return true;
    }
    
    if (api_get_setting('use_session_mode') != 'true') {
        $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        
        $sql = "SELECT tutor_id, status 
                FROM $course_user_table
                WHERE user_id  = '$userid'
                AND   course_code = '$cid'
                LIMIT 1";

        $result = api_sql_query($sql, __FILE__, __LINE__);

        if ( Database::num_rows($result) > 0 ) {
            // this  user have a recorded state for this course
            $cuData = Database::fetch_array($result);

            $is_courseMember = true;
            $is_courseTutor = ($cuData['tutor_id' ] == 1);
            $is_courseAdmin = ($cuData['status'] == 1);

        } else {
             // this user has no status related to this course 
            $is_courseMember = false;
            $is_courseAdmin = false;
            $is_courseTutor = false;
        }

        $is_courseAdmin = ($is_courseAdmin || $is_platformAdmin);
    } else {
        $tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
                    
        $sql = "SELECT 
                    tutor_id, status, role
                FROM $tbl_course_user
                WHERE 
                    user_id  = '$userid'
                AND 
                    course_code = '$cid'
                LIMIT 1";

        $result = api_sql_query($sql, __FILE__, __LINE__);

        if ( Database::num_rows($result) > 0 ) {
            // this  user have a recorded state for this course
            $cuData = Database::fetch_array($result);

            $_courseUser['role'] = $cuData['role'];
            $is_courseMember     = true;
            $is_courseTutor      = ($cuData['tutor_id' ] == 1);
            $is_courseAdmin      = ($cuData['status'] == 1);
        }
        if ( !$is_courseAdmin ) {
            // this user has no status related to this course
            // is it the session coach or the session admin ?
            $tbl_session = 
                Database::get_main_table(TABLE_MAIN_SESSION);
            $tbl_session_course = 
                Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
            $tbl_session_course_user = 
                Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            
            $sql = "SELECT 
                        session.id_coach, session_admin_id, session.id
                    FROM 
                        $tbl_session as session
                    INNER JOIN $tbl_session_course
                        ON session_rel_course.id_session = session.id
                        AND session_rel_course.course_code = '$cid'
                    LIMIT 1";

            $result = api_sql_query($sql, __FILE__, __LINE__);
            $row = api_store_result($result);
            
            if ( $row[0]['id_coach'] == $userid ) {
                $_courseUser['role'] = 'Professor';
                $is_courseMember = true;
                $is_courseTutor = true;
                $is_courseAdmin = false;
                $is_courseCoach = true;
                $is_sessionAdmin = false;

                api_session_register('_courseUser');
            } elseif ( $row[0]['session_admin_id'] == $userid ) {
                $_courseUser['role'] = 'Professor';
                $is_courseMember = false;
                $is_courseTutor = false;
                $is_courseAdmin = false;
                $is_courseCoach = false;
                $is_sessionAdmin = true;
            } else {
                // Check if the current user is the course coach
                $sql = "SELECT 1
                        FROM $tbl_session_course
                        WHERE session_rel_course.course_code = '$cid'
                        AND session_rel_course.id_coach = '$userid'
                        LIMIT 1";

                $result = api_sql_query($sql,__FILE__,__LINE__);

                if ($row = Database::fetch_array($result)) {
                    $_courseUser['role'] = 'Professor';
                    $is_courseMember = true;
                    $is_courseTutor = true;
                    $is_courseCoach = true;
                    $is_sessionAdmin = false;
                    
                    $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
                    
                    $sql = "SELECT status FROM $tbl_user 
                            WHERE  user_id = $userid  LIMIT 1";

                    $result = api_sql_query($sql);

                    if ( Database::result($result, 0, 0) == 1 ){
                        $is_courseAdmin = true;
                    } else {
                        $is_courseAdmin = false;
                    }              
                } else {
                    // Check if the user is a student is this session
                    $sql = "SELECT  id
                            FROM    $tbl_session_course_user
                            WHERE   id_user  = '$userid'
                            AND     course_code = '$cid'
                            LIMIT 1";

                    if ( Database::num_rows($result) > 0 ) {
                        // this  user have a recorded state for this course
                        while ( $row = Database::fetch_array($result) ) {
                            $is_courseMember     = true;
                            $is_courseTutor      = false;
                            $is_courseAdmin      = false;
                            $is_sessionAdmin     = false;
                        }
                    }
                }
            }
        }
    }

    $is_allowed_in_course = false;

    switch ( $visibility ) {
        case COURSE_VISIBILITY_OPEN_WORLD:
            $is_allowed_in_course = true;
        break;
        case COURSE_VISIBILITY_OPEN_PLATFORM:
            if (isset($userid))
                $is_allowed_in_course = true;
        break;
        case COURSE_VISIBILITY_REGISTERED:
        case COURSE_VISIBILITY_CLOSED:
            if ($is_platformAdmin || $is_courseMember || $is_courseAdmin)
                $is_allowed_in_course = true;
        break;
        default:
            $is_allowed_in_course = false;
        break;
    }

    return $is_allowed_in_course;
}

/**
 * Returns whether an element (forum, message, survey ...) belongs to a session or not
 * @param String the tool of the element
 * @param int the element id in database
 * @param int the session_id to compare with element session id
 * @return boolean true if the element is in the session, false else
 */
function api_is_element_in_the_session($tool, $element_id, $session_id=null) {
	if (is_null($session_id)) {
		$session_id = intval($_SESSION['id_session']);
	}
	
	// get informations to build query depending of the tool
	switch ($tool) {
		case TOOL_SURVEY : 
			$table_tool = Database::get_course_table(TABLE_SURVEY);
			$key_field = 'survey_id';
			break;
		case TOOL_ANNOUNCEMENT : 
			$table_tool = Database::get_course_table(TABLE_ANNOUNCEMENT);
			$key_field = 'id';
			break;
		case TOOL_AGENDA : 
			$table_tool = Database::get_course_table(TABLE_AGENDA);
			$key_field = 'id';
			break;
		case TOOL_GROUP :
			$table_tool = Database::get_course_table(TABLE_GROUP);
			$key_field = 'id';
			break;
		default: return false;
	}
	
	
	$sql = 'SELECT session_id FROM '.$table_tool.' WHERE '.$key_field.'='.intval($element_id);
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	if ($element_session_id = Database::result($rs, 0, 0)) {
		if ($element_session_id == intval($session_id)) { // element belongs to the session
			return true;
		}
	}
	return false;
}

/**
 * replaces "forbidden" characters in a filename string
 *
 * @author - Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author - Ren� Haentjens, UGent (RH)
 * @param  - string $filename
 * @param  - string $strict (optional) remove all non-ASCII
 * @return - the cleaned filename
 */

function replace_dangerous_char($filename, $strict = 'loose')
{
	$filename = ereg_replace("\.+$", "", substr(strtr(ereg_replace(
	    "[^!-~\x80-\xFF]", "_", trim($filename)), '\/:*?"<>|\'',
        /* Keep C1 controls for UTF-8 streams */  '-----_---_'), 0, 250));
	if ($strict != 'strict') return $filename;

	return ereg_replace("[^!-~]", "x", $filename);
}

/**
 * Fixes the $_SERVER["REQUEST_URI"] that is empty in IIS6.
 * @author Ivan Tcholakov, 28-JUN-2006.
 */
function api_request_uri()
{
   if (!empty($_SERVER['REQUEST_URI']))
   {
      return $_SERVER['REQUEST_URI'];
   }
   else
   {
      $uri = $_SERVER['SCRIPT_NAME'];
      if (!empty($_SERVER['QUERY_STRING']))
      {
         $uri .= '?'.$_SERVER['QUERY_STRING'];
      }
      $_SERVER['REQUEST_URI'] = $uri;
      return $uri;
   }
}

/**
 * Creates the "include_path" php-setting, following the rule that
 * PEAR packages of Dokeos should be read before other external packages.
 * To be used in global.inc.php only.
 * @author Ivan Tcholakov, 06-NOV-2008.
 */
function api_create_include_path_setting()
{
	$include_path = ini_get('include_path');
	if (!empty($include_path))
	{
		$include_path_array = explode(PATH_SEPARATOR, $include_path);
		$dot_found = array_search('.', $include_path_array);
		if ($dot_found !== false)
		{
			$result = array();
			foreach ($include_path_array as $path)
			{
				$result[] = $path;
				if ($path == '.')
				{
					// The path of Dokeos PEAR packages is to be inserted after the current directory path.
					$result[] = api_get_path(LIBRARY_PATH).'pear';
				}
			}
			return implode(PATH_SEPARATOR, $result);
		}
		// Current directory is not listed in the include_path setting, low probability is here.
		return api_get_path(LIBRARY_PATH).'pear'.PATH_SEPARATOR.$include_path;
	}
	// The include_path setting is empty, low probability is here.
	return api_get_path(LIBRARY_PATH).'pear';
}

/** Gets the current access_url id of the Dokeos Platform
 * @author Julio Montoya <gugli100@gmail.com>
 * @return int access_url_id of the current Dokeos Installation 
 */
function api_get_current_access_url_id()
{
	$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
	$path = api_get_path(WEB_PATH);  
	$sql = "SELECT id FROM $access_url_table WHERE url = '".$path."'";
	$result = api_sql_query($sql); 
	if (Database::num_rows($result)>0) {
		$access_url_id = Database::result($result, 0, 0);
		return $access_url_id;
	} else {
		return -1;
	}	
}

/** Gets the registered urls from a given user id
 * @author Julio Montoya <gugli100@gmail.com>
 * @return int user id  
 */
function api_get_access_url_from_user($user_id) 
{
	$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$table_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);		
	$sql = "SELECT access_url_id FROM $table_url_rel_user url_rel_user INNER JOIN $table_url u 
		    ON (url_rel_user.access_url_id = u.id)
		    WHERE user_id = ".Database::escape_string($user_id);
	$result = api_sql_query($sql,  __FILE__, __LINE__);
	$url_list=array();
	while ($row = Database::fetch_array($result,'ASSOC')) {
		$url_list[] = $row['access_url_id'];
	}			
	return $url_list;		
}	


/**
 * @author florespaz@bidsoftperu.com
 * @param integer $user_id
 * @param string $course_code
 * @return integer status
 */
function api_get_status_of_user_in_course ($user_id,$course_code) {
	$tbl_rel_course_user=Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$sql='SELECT status FROM '.$tbl_rel_course_user.' WHERE user_id='.$user_id.' AND course_code="'.$course_code.'";';
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$row_status=Database::fetch_array($result,'ASSOC');
	return $row_status['status'];
}

/**
 * Checks whether the curent user is in a course or not.
 * 
 * @param	string	The course code - optional (takes it from session if not given)
 * @return	boolean
 * @author	Yannick Warnier <yannick.warnier@dokeos.com>
 */
function api_is_in_course($course_code = null) {
	if (isset($_SESSION['_course']['sysCode'])) {
		if (!empty($course_code)) {
			if ($course_code == $_SESSION['_course']['sysCode']) return true; else return false;
		} else {
			return true;
		}
	}
	return false;
}

/**
 * Checks whether the curent user is in a group or not.
 * 
 * @param	string	The group id - optional (takes it from session if not given)
 * @param	string	The course code - optional (no additional check by course if course code is not given)
 * @return	boolean	
 * @author	Ivan Tcholakov
 */
function api_is_in_group($group_id = null, $course_code = null) {

	if (!empty($course_code)) {
		if (isset($_SESSION['_course']['sysCode'])) {
			if ($course_code != $_SESSION['_course']['sysCode']) return false;
		} else {
			return false;
		}
	}

	if (isset($_SESSION['_gid']) && $_SESSION['_gid'] != '') {
		if (!empty($group_id)) {
			if ($group_id == $_SESSION['_gid']) return true; else return false;
		} else {
			return true;
		}
	}
	return false;
}

// sys_get_temp_dir() is on php since 5.2.1
if ( !function_exists('sys_get_temp_dir') )
{
    // Based on http://www.phpit.net/
    // article/creating-zip-tar-archives-dynamically-php/2/
    function sys_get_temp_dir()
    {
        // Try to get from environment variable
        if ( !empty($_ENV['TMP']) )
        {
            return realpath( $_ENV['TMP'] );
        }
        else if ( !empty($_ENV['TMPDIR']) )
        {
            return realpath( $_ENV['TMPDIR'] );
        }
        else if ( !empty($_ENV['TEMP']) )
        {
            return realpath( $_ENV['TEMP'] );
        }

        // Detect by creating a temporary file
        else
        {
            // Try to use system's temporary directory
            // as random name shouldn't exist
            $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
            if ( $temp_file )
            {
                $temp_dir = realpath( dirname($temp_file) );
                unlink( $temp_file );
                return $temp_dir;
            }
            else
            {
                return FALSE;
            }
        }
    }
}
/**
 * This function allow know when request sent is XMLHttpRequest
 */
function api_is_xml_http_request() {
	if ($_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest') {
		return true;
	} else {
		return false;
	}
}
/**
 * This function gets the hash in md5 or sha1 (it depends in the platform config) of a given password
 * @param  string password
 * @return string password with the applied hash 
 * */
function api_get_encrypted_password($password,$salt = '') 
{
	global $userPasswordCrypted;
	switch ($userPasswordCrypted){
		case 'md5':
					if (!empty($salt)) {
						$passwordcrypted = md5($password.$salt);	
					} else {
						$passwordcrypted = md5($password);
					}		 
					return $passwordcrypted;
					break;
		case 'sha1':
					if (!empty($salt)) {
						$passwordcrypted = sha1($password.$salt);	
					} else {
						$passwordcrypted = sha1($password);
					}		 
					return $passwordcrypted;
					break;
		case 'none':
					return $password;
					break;
		default:
					if (!empty($salt)) {
						$passwordcrypted = md5($password.$salt);	
					} else {
						$passwordcrypted = md5($password);
					}		 
					return $passwordcrypted;
					break; 
	}
	
}

/** Check if a secret key is valid
 *  @param string $original_key_secret  - secret key from (webservice) client
 *  @param string $security_key - security key from dokeos
 *  @return boolean - true if secret key is valid, false otherwise 
 */
function api_is_valid_secret_key($original_key_secret,$security_key) {
    global $_configuration;
    if ( $original_key_secret == sha1($security_key)) {
            return true; //secret key is incorrect
    } else {
        return false;
    }       
}
/**
 * Check if a user is into course
 * @param string $course_id - the course id
 * @param string $user_id - the user id
 */
function api_is_user_of_course ($course_id,$user_id) {
	$tbl_course_rel_user=Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$sql='SELECT user_id FROM '.$tbl_course_rel_user.' WHERE course_code="'.Database::escape_string($course_id).'" AND user_id="'.Database::escape_string($user_id).'"';
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if (Database::num_rows($result)==1) {
		return true;
	} else {
		return false;
	}
}

/**
 * Checks whether the server's operating system is Windows (TM).
 * @return boolean - true if the operating system is Windows, false otherwise 
 */
function api_is_windows_os() {
	if (function_exists("php_uname")) {
		// php_uname() exists since PHP 4.0.2., according to the documentation.
		// We expect that this function will always work for Dokeos 1.8.x.
		$os = php_uname();
	}
	// The following methods are not needed, but let them stay, just in case.
	elseif (isset($_ENV['OS'])) {
		// Sometimes $_ENV['OS'] the may not be present (bugs?)
		$os = $_ENV['OS'];
	}
	elseif (defined('PHP_OS')) {
		// PHP_OS means on which OS PHP was compiled, this is why
		// using PHP_OS is the last choice for detection.
		$os = PHP_OS;
	} else {
		$os = '';
	}

	return strtolower(substr($os, 0, 3 )) === 'win' ? true : false;
}

// This function converts URLs into local file names.
// Purpose: To help diagnosing or making workarounds concerning problems, caused by getimagesize().
//
// Usage:
// $imagesize = @getimagesize($image);  ---->  $imagesize = @getimagesize(api_url_to_local_path($image));
//
// First implementation: Dokeos 1.8.6.
// Ivan Tcholakov, 23-SEP-2008.
// GNU General Public License version 2 or any later (Free Software Foundation).
function api_url_to_local_path($url)
{
	// Check for a valid URL.
	if (!preg_match('/https?:\/\/(.*?):{0,1}([0-9]*)(\/)(.*?)/', $url))
	{
		return $url;	// Return non-URLs without modifications.
	}

	$original_url = $url;
	$url = urldecode($url);

	// A special case:
	// If the URL points the document download script directly (without mod-rewrite translation),
	// we will translate this URL into a simple one, in order to process it easy below.
	// For example:
	// http://localhost/dokeos/main/document/download.php?doc_url=/image.png&cDir=/
	// becomes
	// http://localhost/dokeos/courses/TEST/document/image.png
	//
	if (preg_match('/(.*)main\/document\/download.php\?doc_url=\/(.*)&cDir=\/(.*)?/', $url, $matches))
	{
		global $_cid, $_course;
		if (!empty($_cid) and $_cid != -1 and isset($_course)) // Inside a course?
		{
			$url = $matches[1].'courses/'.$_course['path'].'/document/'.str_replace('//', '/', $matches[3].'/'.$matches[2]);
		}
		else
		{
			return $original_url;	// Not inside a course, return then the URL "as is".
		}
	}

	// Generally, we have to deal with URLs like this:
	// http://localhost/dokeos/courses/TEST/document/image.png?cidReq=TEST
	// Let us remove possibe URL's parameters:
	// http://localhost/dokeos/courses/TEST/document/image.png
	$tmp = explode('?', $url);
	$array_url = explode ('/', $tmp[0]);

	// Pulling out the filename image.png
	// The rest of the URL is http://localhost/dokeos/courses/TEST/document
	$file_name = array_pop($array_url);

	// api_get_path(WEB_PATH) returns for example http://localhost/dokeos/
	$array_web_path = explode ('/', api_get_path(WEB_PATH));

	// Getting the relative local path.
	// http://localhost/dokeos/courses/TEST/document
	// http://localhost/dokeos/
	// ---------------------------------------------
	//                         courses/TEST/document
	$array_local_path = array_values(array_diff($array_url, $array_web_path));
	$local_path = implode('/', $array_local_path);

	// Sanity check - you may have seen this comment in dokeos/main/document/download.php:
	//mod_rewrite can change /some/path/ to /some/path// in some cases, so clean them all off (Ren�)
	// Let us remove double slashes, if any (triple too, etc.).
	$local_path = preg_replace('@/{2,}@', '/', $local_path);

	// Finally, we will concatenate: the system root (for example /var/www/), the relative local path, and the file name.
	// /var/www/courses/TEST/document/image.png
	return str_replace("\\", '/', api_get_path(SYS_PATH)).(empty($local_path) ? '' : $local_path.'/').$file_name;
}

/**
 * This function resizes an image, with preserving its proportions (or aspect ratio).
 * @author Ivan Tcholakov, MAY-2009.
 * @param int $image			System path or URL of the image
 * @param int $target_width		Targeted width
 * @param int $target_height	Targeted height
 * @return array				Calculated new width and height
 */
function api_resize_image($image, $target_width, $target_height) {
	$image_properties = @getimagesize(api_url_to_local_path($image)); // We have to call getimagesize() in a safe way.
	$image_width = $image_properties[0];
	$image_height = $image_properties[1];
	return api_calculate_image_size($image_width, $image_height, $target_width, $target_height);
}

/**
 * This function calculates new image size, with preserving image's proportions (or aspect ratio).
 * @author Ivan Tcholakov, MAY-2009.
 * @author The initial idea has been taken from code by Patrick Cool, MAY-2004.
 * @param int $image_width		Initial width
 * @param int $image_height		Initial height
 * @param int $target_width		Targeted width
 * @param int $target_height	Targeted height
 * @return array				Calculated new width and height
 */
function api_calculate_image_size($image_width, $image_height, $target_width, $target_height) {
	// Only maths is here.
	$result = array('width' => $image_width, 'height' => $image_height);
	if ($image_width <= 0 || $image_height <= 0) {
		return $result;
	}
	$resize_factor_width = $target_width / $image_width;
	$resize_factor_height = $target_height / $image_height;
	$delta_width = $target_width - $image_width * $resize_factor_height;
	$delta_height = $target_height - $image_height * $resize_factor_width;
	if ($delta_width > $delta_height) {
		$result['width'] = ceil($image_width * $resize_factor_height);
		$result['height'] = ceil($image_height * $resize_factor_height);
	}
	elseif ($delta_width < $delta_height) {
		$result['width'] = ceil($image_width * $resize_factor_width);
		$result['height'] = ceil($image_height * $resize_factor_width);
	}
	else {
		$result['width'] = ceil($target_width);
		$result['height'] = ceil($target_height);
	}
	return $result;
}
