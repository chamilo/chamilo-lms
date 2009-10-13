<?php
/*
===============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Julio Montoya
	Copyright (c) Hugues Peeters
	Copyright (c) Christophe Gesché
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)
	Copyright (c) Patrick Cool
	Copyright (c) Olivier Brouckaert
	Copyright (c) Toon Van Hoecke
	Copyright (c) Denes Nagy
	Copyright (c) Isaac Flores
	Copyright (c) Ivan Tcholakov

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
/** global status of a user: low security,it's necessary for inserting data from the teacher */
define('COURSEMANAGERLOWSECURITY', 10);

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


//SESSION VISIBILITY CONSTANTS
define('SESSION_VISIBLE_READ_ONLY', 1);
define('SESSION_VISIBLE', 2);
define('SESSION_INVISIBLE',3);

define('SUBSCRIBE_ALLOWED', 1);
define('SUBSCRIBE_NOT_ALLOWED', 0);
define('UNSUBSCRIBE_ALLOWED', 1);
define('UNSUBSCRIBE_NOT_ALLOWED', 0);

//CONSTANTS defining all tools, using the english version
/*
When you add a new tool you must add it into function api_get_tools_lists() too
 */
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

// CONSTANT defining the default HotPotatoes files directory
define('DIR_HOTPOTATOES','/HotPotatoes_files');

// event logs types
define('LOG_COURSE_DELETE', 'course_deleted');
define('LOG_COURSE_CREATE', 'course_created');
define('LOG_USER_DELETE', 'user_deleted');
define('LOG_USER_CREATE', 'user_created');
define('LOG_SESSION_CREATE', 'session_created');
define('LOG_SESSION_DELETE', 'session_deleted');
define('LOG_SESSION_CATEGORY_CREATE', 'session_category_created');
define('LOG_SESSION_CATEGORY_DELETE', 'session_category_deleted');
define('LOG_CONFIGURATION_SETTINGS_CHANGE', 'settings_changed');
define('LOG_SUBSCRIBE_USER_TO_COURSE', 'user_subscribed');
define('LOG_UNSUBSCRIBE_USER_FROM_COURSE', 'user_unsubscribed');
// event logs data types
define('LOG_COURSE_CODE', 'course_code');
define('LOG_USER_ID', 'user_id');
define('LOG_SESSION_ID', 'session_id');
define('LOG_SESSION_CATEGORY_ID', 'session_category_id');
define('LOG_CONFIGURATION_SETTINGS_CATEGORY', 'settings_category');
define('LOG_CONFIGURATION_SETTINGS_VARIABLE', 'settings_variable');

// Specification for usernames:
// 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 20 characters maximal length.
// 2. Empty username is formally valid, but it is reserved for the anonymous user.
define('USERNAME_MAX_LENGTH', 20);
define('USERNAME_PURIFIER', '/[^0-9A-Za-z_\.]/');
define('USERNAME_PURIFIER_SHALLOW', '/\s/');

// Constants for detection some important PHP5 subversions.
define('IS_PHP_52', !((float)PHP_VERSION < 5.2));
define('IS_PHP_53', !((float)PHP_VERSION < 5.3));

// This constant is a result of Windows OS detection, it has a boolean value:
// true whether the server runs on Windows OS, false otherwise.
define('IS_WINDOWS_OS', api_is_windows_os());

// Checks for installed optional php-extensions.
define('INTL_INSTALLED', function_exists('intl_get_error_code'));	// intl extension (from PECL), it is installed by default as of PHP 5.3.0
define('ICONV_INSTALLED', function_exists('iconv'));				// iconv extension, for PHP5 on Windows it is installed by default.
define('MBSTRING_INSTALLED', function_exists('mb_strlen'));			// mbstring extension.

// Patterns for processing paths.									// Examples:
define('REPEATED_SLASHES_PURIFIER', '/\/{2,}/');					// $path = preg_replace(REPEATED_SLASHES_PURIFIER, '/', $path);
define('VALID_WEB_PATH', '/https?:\/\/[^\/]*(\/.*)?/i');			// $is_valid_path = preg_match(VALID_WEB_PATH, $path);
define('VALID_WEB_SERVER_BASE', '/https?:\/\/[^\/]*/i');			// $new_path = preg_replace(VALID_WEB_SERVER_BASE, $new_base, $path);

// Constants for api_get_path() and api_get_path_type(), etc. - registered path types.
define('WEB_PATH', 'WEB_PATH');
define('SYS_PATH', 'SYS_PATH');
define('REL_PATH', 'REL_PATH');
define('WEB_SERVER_ROOT_PATH', 'WEB_SERVER_ROOT_PATH');
define('SYS_SERVER_ROOT_PATH', 'SYS_SERVER_ROOT_PATH');
define('WEB_COURSE_PATH', 'WEB_COURSE_PATH');
define('SYS_COURSE_PATH', 'SYS_COURSE_PATH');
define('REL_COURSE_PATH', 'REL_COURSE_PATH');
define('REL_CODE_PATH', 'REL_CODE_PATH');
define('WEB_CODE_PATH', 'WEB_CODE_PATH');
define('SYS_CODE_PATH', 'SYS_CODE_PATH');
define('SYS_LANG_PATH', 'SYS_LANG_PATH');
define('WEB_IMG_PATH', 'WEB_IMG_PATH');
define('WEB_CSS_PATH', 'WEB_CSS_PATH');
define('GARBAGE_PATH', 'GARBAGE_PATH'); // Deprecated?
define('SYS_PLUGIN_PATH', 'SYS_PLUGIN_PATH');
define('PLUGIN_PATH', 'SYS_PLUGIN_PATH'); // deprecated
define('WEB_PLUGIN_PATH', 'WEB_PLUGIN_PATH');
define('SYS_ARCHIVE_PATH', 'SYS_ARCHIVE_PATH');
define('WEB_ARCHIVE_PATH', 'WEB_ARCHIVE_PATH');
define('INCLUDE_PATH', 'INCLUDE_PATH');
define('LIBRARY_PATH', 'LIBRARY_PATH');
define('CONFIGURATION_PATH', 'CONFIGURATION_PATH');
define('WEB_LIBRARY_PATH', 'WEB_LIBRARY_PATH');
// Constants for requesting path conversion.
define('TO_WEB', 'TO_WEB');
define('TO_SYS', 'TO_SYS');
define('TO_REL', 'TO_REL');
// Paths to regidtered specific resource files (scripts, players, etc.)
define('FLASH_PLAYER_AUDIO', '{FLASH_PLAYER_AUDIO}');
define('FLASH_PLAYER_VIDEO', '{FLASH_PLAYER_VIDEO}');
define('SCRIPT_SWFOBJECT', '{SCRIPT_SWFOBJECT}');
define('SCRIPT_ASCIIMATHML', '{SCRIPT_ASCIIMATHML}');


/*
==============================================================================
		MAIN API EXTENSIONS
==============================================================================
*/

require_once dirname(__FILE__).'/internationalization.lib.php';

/*
==============================================================================
		PATHS & FILES - ROUTINES
==============================================================================
*/

/**
 *	Returns a full path to a certain Dokeos area, which you specify through a parameter.
 *	See $_configuration['course_folder'] in the configuration.php to alter the WEB_COURSE_PATH and SYS_COURSE_PATH parameters.
 *	@param string $type				The requested path type (a defined constant), see the examples.
 *	@param string $path (optional)	A path which type is to be converted. Also, it may be a defined constant for a path.
 *	This parameter has meaning when $type parameter has one of the following values: TO_WEB, TO_SYS, TO_REL. Otherwise it is ignored.
 *	@return string					The requested path or the converted path.
 *
 *	A terminology note:
 *	The defined constants used by this function contain the abbreviations WEB, REL, SYS with the following meaning for types:
 *	WEB - an absolute URL (we often call it web-path),
 *	example: http://www.mydokeos.com/dokeos/courses/COURSE01/document/lesson01.html;
 *	REL - represents a semi-absolute URL - a web-path, which is relative to the root web-path of the server, without server's base,
 *	example: /dokeos/courses/COURSE01/document/lesson01.html;
 *	SYS - represents an absolute path inside the scope of server's file system,
 *	/var/www/dokeos/courses/COURSE01/document/lesson01.html or
 *	C:/Inetpub/wwwroot/dokeos/courses/COURSE01/document/lesson01.html.
 *	In some abstract sense we can consider these three path types as absolute.
 *
 *	Notes about the current behaviour model:
 *	1. Windows back-slashes are converted to slashes in the result.
 *	2. A semi-absolute web-path is detected by its leading slash. On Linux systems, absolute system paths start with
 *	a slash too, so an additional check about presense of leading system server base is implemented. For example, the function is
 *	able to distinguish type difference between /var/www/dokeos/courses/ (SYS) and /dokeos/courses/ (REL).
 *	3. The function api_get_path() returns only these three types of paths, which in some sense are absolute. The function has
 *	no a mechanism for processing relative web/system paths, such as: lesson01.html, ./lesson01.html, ../css/my_styles.css.
 *	It has not been identified as needed yet.
 *	4. Also, resolving the meta-symbols "." and ".." withiin paths has not been implemented, it is to be identified as needed.
 *
 * 	@example
 *	Assume that your server root is /var/www/ dokeos is installed in a subfolder dokeos/ and the URL of your campus is http://www.mydokeos.com
 * 	The other configuration paramaters have not been changed.
 *
 * 	This is how we can retireve mosth used paths, for common purpose:
 *	api_get_path(WEB_SERVER_ROOT_PATH)			http://www.mydokeos.com/
 *	api_get_path(SYS_SERVER_ROOT_PATH)			/var/www/ - This is the physical folder where the system Dokeos has been placed. It is not always equal to $_SERVER['DOCUMENT_ROOT'].
 * 	api_get_path(WEB_PATH)						http://www.mydokeos.com/dokeos/
 * 	api_get_path(SYS_PATH)						/var/www/dokeos/
 * 	api_get_path(REL_PATH)						/dokeos/
 * 	api_get_path(WEB_COURSE_PATH)				http://www.mydokeos.com/dokeos/courses/
 * 	api_get_path(SYS_COURSE_PATH)				/var/www/dokeos/courses/
 *	api_get_path(REL_COURSE_PATH)				/dokeos/courses/
 * 	api_get_path(REL_CODE_PATH)					/dokeos/main/
 * 	api_get_path(WEB_CODE_PATH)					http://www.mydokeos.com/dokeos/main/
 * 	api_get_path(SYS_CODE_PATH)					/var/www/dokeos/main/
 * 	api_get_path(SYS_LANG_PATH)					/var/www/dokeos/main/lang/
 * 	api_get_path(WEB_IMG_PATH)					http://www.mydokeos.com/dokeos/main/img/
 *	api_get_path(WEB_CSS_PATH)					http://www.mydokeos.com/dokeos/main/css/
 * 	api_get_path(GARBAGE_PATH)					Deprecated?
 * 	api_get_path(WEB_PLUGIN_PATH)				http://www.mydokeos.com/dokeos/plugin/
 * 	api_get_path(SYS_PLUGIN_PATH)				/var/www/dokeos/plugin/
 * 	api_get_path(WEB_ARCHIVE_PATH)				http://www.mydokeos.com/dokeos/archive/
 * 	api_get_path(SYS_ARCHIVE_PATH)				/var/www/dokeos/archive/
 *	api_get_path(INCLUDE_PATH)					/var/www/dokeos/main/inc/
 * 	api_get_path(WEB_LIBRARY_PATH)				http://www.mydokeos.com/dokeos/main/inc/lib/
 * 	api_get_path(LIBRARY_PATH)					/var/www/dokeos/main/inc/lib/
 * 	api_get_path(CONFIGURATION_PATH)			/var/www/dokeos/main/inc/conf/
 *
 *	This is how we retrieve paths of "registerd" resource files (scripts, players, etc.):
 *	api_get_path(TO_WEB, FLASH_PLAYER_AUDIO)	http://www.mydokeos.com/dokeos/main/inc/lib/mediaplayer/player.swf
 *	api_get_path(TO_WEB, FLASH_PLAYER_VIDEO)	http://www.mydokeos.com/dokeos/main/inc/lib/mediaplayer/player.swf
 *	api_get_path(TO_SYS, SCRIPT_SWFOBJECT)		/var/www/dokeos/main/inc/lib/swfobject/swfobject.js
 *	api_get_path(TO_REL, SCRIPT_ASCIIMATHML)	/dokeos/main/inc/lib/asciimath/ASCIIMathML.js
 *	...
 *
 *	We can convert arbitrary paths, that are not registered (no defined constant).
 *	For guaranteed result, these paths should point inside the systen Dokeos.
 *	Some random examples:
 *	api_get_path(TO_WEB, $_SERVER['REQUEST_URI'])
 *	api_get_path(TO_SYS, $_SERVER['PHP_SELF'])
 *	api_get_path(TO_REL, __FILE__)
 *	...
 */
function api_get_path($path_type, $path = null) {

	static $paths = array(
		WEB_PATH => '',
		SYS_PATH => '',
		REL_PATH => '',
		REL_SYS_PATH => '',
		WEB_SERVER_ROOT_PATH => '',
		SYS_SERVER_ROOT_PATH => '',
		WEB_COURSE_PATH => '',
		SYS_COURSE_PATH => '',
		REL_COURSE_PATH => '',
		REL_CODE_PATH => '',
		WEB_CODE_PATH => '',
		SYS_CODE_PATH => '',
		SYS_LANG_PATH => 'lang/',
		WEB_IMG_PATH => 'img/',
		WEB_CSS_PATH => 'css/',
		GARBAGE_PATH => 'archive/', // Deprecated?
		SYS_PLUGIN_PATH => 'plugin/',
		WEB_PLUGIN_PATH => 'plugin/',
		SYS_ARCHIVE_PATH => 'archive/',
		WEB_ARCHIVE_PATH => 'archive/',
		INCLUDE_PATH => 'inc/',
		LIBRARY_PATH => 'inc/lib/',
		CONFIGURATION_PATH => 'inc/conf/',
		WEB_LIBRARY_PATH => 'inc/lib/'
	);

	static $resource_paths = array(
		FLASH_PLAYER_AUDIO => 'inc/lib/mediaplayer/player.swf',
		FLASH_PLAYER_VIDEO => 'inc/lib/mediaplayer/player.swf',
		SCRIPT_SWFOBJECT => 'inc/lib/swfobject/swfobject.js',
		SCRIPT_ASCIIMATHML => 'inc/lib/asciimath/ASCIIMathML.js'
	);

	static $is_this_function_initialized;

	static $include_path_sys;
	static $server_base_web; // No trailing slash.
	static $server_base_sys; // No trailing slash.
	static $root_web;
	static $root_sys;
	static $root_rel;
	static $code_folder;
	static $course_folder;

	if (!$is_this_function_initialized) {

		global $_configuration;

		$include_path_sys = str_replace('\\', '/', realpath(dirname(__FILE__).'/../')).'/';

		//
		// Configuration data for already installed system.
		//
		$root_sys = $_configuration['root_sys'];
		if (!isset($_configuration['access_url']) || $_configuration['access_url'] == 1 || $_configuration['access_url'] == '') {
			//by default we call the $_configuration['root_web'] we don't query to the DB
			//$url_info= api_get_access_url(1);
			//$root_web = $url_info['url'];
			if (isset($_configuration['root_web'])) {
				$root_web = $_configuration['root_web'];
			}
			// Ivan: Just a formal note, here $root_web stays unset, reason about this is unknown.
		} else {
			//we look into the DB the function api_get_access_url
			//this funcion have a problem because we can't called to the Database:: functions
			$url_info = api_get_access_url($_configuration['access_url']);
			$root_web = $url_info['active'] == 1 ? $url_info['url'] : $_configuration['root_web'];
		}
		$root_rel = $_configuration['url_append'];
		$code_folder = $_configuration['code_append'];
		$course_folder = $_configuration['course_folder'];

		//
		// Support for the installation process.
		// Developers might use the function api_fet_path() directly or indirectly (this is difficult to be traced), at the moment when
		// configuration has not been created yet. This is why this function should be upgraded to return correct results in this case.
		//
		if (!file_exists($include_path_sys.'/conf/configuration.php')) {
			$requested_page_rel = api_get_self();
			if (($pos = strpos($requested_page_rel, 'main/install')) !== false) {
				$root_rel = substr($requested_page_rel, 0, $pos);
				// See http://www.mediawiki.org/wiki/Manual:$wgServer
				$server_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
				$server_name =
					isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME']
					: (isset($_SERVER['HOSTNAME']) ? $_SERVER['HOSTNAME']
					: (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
					: (isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR']
					: 'localhost')));
				if (isset($_SERVER['SERVER_PORT']) && !strpos($server_name, ':')
					&& (($server_protocol == 'http'
					&& $_SERVER['SERVER_PORT'] != 80 ) || ($server_protocol == 'https' && $_SERVER['SERVER_PORT'] != 443 ))) {
					$server_name .= ":" . $_SERVER['SERVER_PORT'];
				}
				$root_web = $server_protocol.'://'.$server_name.$root_rel;
				$root_sys = str_replace('\\', '/', realpath(dirname(__FILE__).'/../../../')).'/';
				$code_folder = 'main/';
				$course_folder = 'courses/';
			}
			// Here we give up, so we don't touch anything.
		}

		// Dealing with trailing slashes.
		$root_web = api_add_trailing_slash($root_web);
		$root_sys = api_add_trailing_slash($root_sys);
		$root_rel = api_add_trailing_slash($root_rel);
		$code_folder = api_add_trailing_slash($code_folder);
		$course_folder = api_add_trailing_slash($course_folder);

		// Web server base and system server base.
		$server_base_web = preg_replace('@'.$root_rel.'$@', '', $root_web); // No trailing slash.
		$server_base_sys = preg_replace('@'.$root_rel.'$@', '', $root_sys); // No trailing slash.

		//
		// Initialization of a table taht contains common-purpose paths.
		//
		$paths[WEB_PATH] = $root_web;
		$paths[SYS_PATH] = $root_sys;
		$paths[REL_PATH] = $root_rel;
		$paths[WEB_SERVER_ROOT_PATH] = $server_base_web.'/';
		$paths[SYS_SERVER_ROOT_PATH] = $server_base_sys.'/';
		$paths[WEB_COURSE_PATH] = $root_web.$course_folder;
		$paths[SYS_COURSE_PATH] = $root_sys.$course_folder;
		$paths[REL_COURSE_PATH] = $root_rel.$course_folder;
		$paths[REL_CODE_PATH] = $root_rel.$code_folder;
		$paths[WEB_CODE_PATH] = $root_web.$code_folder;
		// Elimination of an obsolete configuration setting.
		//$paths[SYS_CODE_PATH] = $GLOBALS['clarolineRepositorySys'];
		$paths[SYS_CODE_PATH] = $root_sys.$code_folder;
		//
		// Now we can switch into api_get_path() "terminology".
		$paths[SYS_LANG_PATH] = $paths[SYS_CODE_PATH].$paths[SYS_LANG_PATH];
		$paths[WEB_IMG_PATH] = $paths[WEB_CODE_PATH].$paths[WEB_IMG_PATH];
		// TODO: This path may depend on the configuration option? To be researched.
		// Maybe a new constant like WEB_USER_CSS_PATH has to be defined?
		$paths[WEB_CSS_PATH] = $paths[WEB_CODE_PATH].$paths[WEB_CSS_PATH];
		//
		$paths[GARBAGE_PATH] = $paths[SYS_PATH].$paths[GARBAGE_PATH]; // Deprecated?
		$paths[SYS_PLUGIN_PATH] = $paths[SYS_PATH].$paths[SYS_PLUGIN_PATH];
		$paths[WEB_PLUGIN_PATH] = $paths[WEB_PATH].$paths[WEB_PLUGIN_PATH];
		$paths[SYS_ARCHIVE_PATH] = $paths[SYS_PATH].$paths[SYS_ARCHIVE_PATH];
		$paths[WEB_ARCHIVE_PATH] = $paths[WEB_PATH].$paths[WEB_ARCHIVE_PATH];
		// A change for Dokeos 1.8.6.2
		// Calculation in the previous way does not rely on configuration settings and in some cases gives unexpected results.
		//$paths[INCLUDE_PATH] = $include_path_sys; // Old behaviour, Dokeos 1.8.6.1.
		$paths[INCLUDE_PATH] = $paths[SYS_CODE_PATH].$paths[INCLUDE_PATH]; // New behaviour, coherrent with the model, Dokeos 1.8.6.2.
		//
		$paths[LIBRARY_PATH] = $paths[SYS_CODE_PATH].$paths[LIBRARY_PATH];
		$paths[CONFIGURATION_PATH] = $paths[SYS_CODE_PATH].$paths[CONFIGURATION_PATH];
		$paths[WEB_LIBRARY_PATH] = $paths[WEB_CODE_PATH].$paths[WEB_LIBRARY_PATH];

		$is_this_function_initialized = true;
	}

	// Shallow purification and validation of input parameters.

	$path_type = trim($path_type);
	$path = trim($path);

	if (empty($path_type)) {
		return null;
	}

	// Retrieving a common-purpose path.

	if (isset($paths[$path_type])) {
		return $paths[$path_type];
	}

	// Retrieving a specific resource path.

	if (isset($resource_paths[$path])) {
		switch ($path_type) {
			case TO_WEB:
				return $paths[WEB_CODE_PATH].$resource_paths[$path];
			case TO_SYS:
				return $paths[SYS_CODE_PATH].$resource_paths[$path];
			case TO_REL:
				return $paths[REL_CODE_PATH].$resource_paths[$path];
			default:
				return null;
		}
	}

	// Common-purpose paths as a second parameter - recognition.

	if (isset($paths[$path])) {
		$path = $paths[$path];
	}

	// Second purification.

	// Replacing Windows back slashes.
	$path = str_replace('\\', '/', $path);
	// Query strings sometimes mighth wrongly appear in non-URLs.
	// Let us check remove them from all types of paths.
	if (($pos = strpos($path, '?')) !== false) {
		$path = substr($path, 0, $pos);
	}

	// Detection of the input path type. Conversion to semi-absolute type ( /dokeos/main/inc/.... ).

	if (preg_match(VALID_WEB_PATH, $path)) {

		// A special case: When a URL points to the document download script directly, without
		// mod-rewrite translation, we have to translate it into an "ordinary" web path.
		// For example:
		// http://localhost/dokeos/main/document/download.php?doc_url=/image.png&cDir=/
		// becomes
		// http://localhost/dokeos/courses/TEST/document/image.png
		// TEST is a course directory name, so called "system course code".
		if (strpos($path, 'download.php') !== false) { // Fast detection first.
			$path = urldecode($path);
			if (preg_match('/(.*)main\/document\/download.php\?doc_url=\/(.*)&cDir=\/(.*)?/', $path, $matches)) {
				$sys_course_code =
					isset($_SESSION['_course']['sysCode'])	// User is inside a course?
						? $_SESSION['_course']['sysCode']	// Yes, then use course's directory name.
						: '{SYS_COURSE_CODE}';				// No, then use a fake code, it may be processed later.
				$path = $matches[1].'courses/'.$sys_course_code.'/document/'.str_replace('//', '/', $matches[3].'/'.$matches[2]);
			}
		}
		// Replacement of the present web server base with a slash '/'.
		$path = preg_replace(VALID_WEB_SERVER_BASE, '/', $path);

	} elseif (strpos($path, $server_base_sys) === 0) {

		$path = preg_replace('@^'.$server_base_sys.'@', '', $path);

	} elseif (strpos($path, '/') === 0) {

		// Leading slash - we assume that this path is semi-absolute (REL),
		// then path is left without furthes modifications.

	} else {

		return null; // Probably implementation of this case won't be needed.

	}

	// Path now is semi-absolute. It is convenient at this moment repeated slashes to be removed.
	$path = preg_replace(REPEATED_SLASHES_PURIFIER, '/', $path);

	// Path conversion to the requested type.

	switch ($path_type) {
		case TO_WEB:
			return $server_base_web.$path;
		case TO_SYS:
			return $server_base_sys.$path;
		case TO_REL:
			return $path;
	}

	return null;
}

/**
 * This function checks whether a given path points inside the system.
 * @param string $path		The path to be tesed. It should be full path, web-absolute (WEB), semi-absolute (REL) or system-absolyte (SYS).
 * @return bool				Returns true when the given path is inside the system, false otherwise.
 */
function api_is_internal_path($path) {
	$path = str_replace('\\', '/', trim($path));
	if (empty($path)) {
		return false;
	}
	if (strpos($path, api_remove_trailing_slash(api_get_path(WEB_PATH))) === 0) {
		return true;
	}
	if (strpos($path, api_remove_trailing_slash(api_get_path(SYS_PATH))) === 0) {
		return true;
	}
	$server_base_web = api_remove_trailing_slash(api_get_path(REL_PATH));
	$server_base_web = empty($server_base_web) ? '/' : $server_base_web;
	if (strpos($path, $server_base_web) === 0) {
		return true;
	}
	return false;
}

/**
 * Adds to a given path a trailing slash if it is necessary (adds "/" character at the end of the string).
 * @param string $path			The input path.
 * @return string				Returns the modified path.
 */
function api_add_trailing_slash($path) {
	return substr($path, -1) == '/' ? $path : $path.'/';
}

/**
 * Removes from a given path the trailing slash if it is necessary (removes "/" character from the end of the string).
 * @param string $path			The input path.
 * @return string				Returns the modified path.
 */
function api_remove_trailing_slash($path) {
	return substr($path, -1) == '/' ? substr($path, 0, -1) : $path;
}


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
 * @return	boolean True if the user has access to the current course or is out of a course context, false otherwise
 * @todo replace global variable
 * @author Roan Embrechts
 */
function api_protect_course_script($print_headers = false) {
	global $is_allowed_in_course;
	if (!$is_allowed_in_course) {
		api_not_allowed($print_headers);
		return false;
	}
	return true;
}

/**
 * Function used to protect an admin script.
 * The function blocks access when the user has no platform admin rights.
 * This is only the first proposal, test and improve!
 *
 * @author Roan Embrechts
 */
function api_protect_admin_script($allow_sessions_admins = false) {
	if (!api_is_platform_admin($allow_sessions_admins)) {
		include api_get_path(INCLUDE_PATH).'header.inc.php';
		api_not_allowed();
		return false;
	}
	return true;
}

/**
 * Function used to prevent anonymous users from accessing a script.
 *
 * @author Roan Embrechts
 */
function api_block_anonymous_users() {
	global $_user;
	if (!(isset($_user['user_id']) && $_user['user_id']) || api_is_anonymous($_user['user_id'], true)) {
		include api_get_path(INCLUDE_PATH).'header.inc.php';
		api_not_allowed();
		return false;
	}
	return true;
}


/*
==============================================================================
		ACCESSOR FUNCTIONS
		don't access kernel variables directly,
		use these functions instead
==============================================================================
*/

/**
 * @return an array with the navigator name and version
 */
function api_get_navigator() {
	$navigator = 'Unknown';
	$version = 0;
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== false) {
		$navigator = 'Opera';
		list (, $version) = explode('Opera', $_SERVER['HTTP_USER_AGENT']);
	} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
		$navigator = 'Internet Explorer';
		list (, $version) = explode('MSIE', $_SERVER['HTTP_USER_AGENT']);
	} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Gecko') !== false) {
		$navigator = 'Mozilla';
		list (, $version) = explode('; rv:', $_SERVER['HTTP_USER_AGENT']);
	} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape') !== false) {
		$navigator = 'Netscape';
		list (, $version) = explode('Netscape', $_SERVER['HTTP_USER_AGENT']);
	}
	// Added by Ivan Tcholakov, 23-AUG-2008.
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'Konqueror') !== false) {
		$navigator = 'Konqueror';
		list (, $version) = explode('Konqueror', $_SERVER['HTTP_USER_AGENT']);
	}
	if (stripos($_SERVER['HTTP_USER_AGENT'], 'applewebkit') !== false) {
		$navigator = 'AppleWebKit';
		list (, $version) = explode('Version/', $_SERVER['HTTP_USER_AGENT']);
	}
	if (stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false) {
		$navigator = 'Safari';
		list (, $version) = explode('Version/', $_SERVER['HTTP_USER_AGENT']);
	}
	//
	$version = doubleval($version);
	if (strpos($version, '.') === false) {
		$version = number_format(doubleval($version), 1);
	}
	return array ('name' => $navigator, 'version' => $version);
}

/**
 * @return True if user selfregistration is allowed, false otherwise.
 */
function api_is_self_registration_allowed() {
	return isset($GLOBALS['allowSelfReg']) ? $GLOBALS['allowSelfReg'] : false;
}

/**
 * This function returns the id of the user which is stored in the $_user array.
 *
 * @example The function can be used to check if a user is logged in
 * 			if (api_get_user_id())
 * @return integer the id of the current user
 */
function api_get_user_id() {
	return empty($GLOBALS['_user']['user_id']) ? 0 : $GLOBALS['_user']['user_id'];
}

/**
 * Get the list of courses a specific user is subscribed to
 * @param	int		User ID
 * @param	boolean	Whether to get session courses or not - NOT YET IMPLEMENTED
 * @return	array	Array of courses in the form [0]=>('code'=>xxx,'db'=>xxx,'dir'=>xxx,'status'=>d)
 */
function api_get_user_courses($userid, $fetch_session = true) {
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
	$result = Database::query($sql_select_courses, __FILE__, __LINE__);
	if ($result === false) { return array(); }
	while ($row = Database::fetch_array($result)) {
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
		return $GLOBALS['_user'];
	}
	$sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_USER)." WHERE user_id='".Database::escape_string($user_id)."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if (Database::num_rows($result) > 0) {
		$result_array = Database::fetch_array($result);
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
 * Find all the information about a user from username instead of user id
 * @param $username (string): the username
 * @return $user_info (array): user_id, lastname, firstname, username, email, ...
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
function api_get_user_info_from_username($username = '') {
	if (empty($username)) { return false; }
	global $tbl_user;
	$sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_USER)." WHERE username='".Database::escape_string($username)."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if (Database::num_rows($result) > 0) {
		$result_array = Database::fetch_array($result);
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
	return $GLOBALS['_cid'];
}

/**
 * Returns the current course directory
 *
 * This function relies on api_get_course_info()
 * @param	string	The course code - optional (takes it from session if not given)
 * @return	string	The directory where the course is located inside the Dokeos "courses" directory
 * @author	Yannick Warnier <yannick.warnier@dokeos.com>
 */
function api_get_course_path($course_code = null) {
	$info = !empty($course_code) ? api_get_course_info($course_code) : api_get_course_info();
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
		$course_info = api_get_course_info($course_code);
		$table = Database::get_course_table(TABLE_COURSE_SETTING, $course_info['dbName']);
	} else {
		$table = Database::get_course_table(TABLE_COURSE_SETTING);
	}
	$setting_name = Database::escape_string($setting_name);
	$sql = "SELECT value FROM $table WHERE variable = '$setting_name'";
	$res = Database::query($sql, __FILE__, __LINE__);
	if (Database::num_rows($res) > 0) {
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
	$res = Database::query($sql, __FILE__, __LINE__);
	if (Database::num_rows($res) > 0) {
		$row = Database::fetch_array($res);
		//error_log('api_get_anonymous_id() returns '.$row['user_id'], 0);
		return $row['user_id'];
	}
	//no anonymous user was found
	return 0;
}

/**
 * Returns the cidreq parameter name + current course id
 */
function api_get_cidreq() {
	return empty($GLOBALS['_cid']) ? '' : 'cidReq='.htmlspecialchars($GLOBALS['_cid']);
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
function api_get_course_info($course_code = null) {
	if (!empty($course_code)) {
		$course_code = Database::escape_string($course_code);
		$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
		$course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
		$sql = "SELECT `course`.*, `course_category`.`code` `faCode`, `course_category`.`name` `faName`
				 FROM $course_table
				 LEFT JOIN $course_cat_table
				 ON `course`.`category_code` =  `course_category`.`code`
				 WHERE `course`.`code` = '$course_code'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$_course = array();
		if (Database::num_rows($result) > 0) {
			global $_configuration;
			$cData = Database::fetch_array($result);
			$_course['id'           ]         = $cData['code'           ]; //auto-assigned integer
			$_course['name'         ]         = $cData['title'          ];
			$_course['official_code']         = $cData['visual_code'    ]; // use in echo
			$_course['sysCode'      ]         = $cData['code'           ]; // use as key in db
			$_course['path'         ]         = $cData['directory'      ]; // use as key in path
			$_course['dbName'       ]         = $cData['db_name'        ]; // use as key in db list
			$_course['dbNameGlu'    ]         = $_configuration['table_prefix'] . $cData['db_name'] . $_configuration['db_glue']; // use in all queries
			$_course['titular'      ]         = $cData['tutor_name'     ];
			$_course['language'     ]         = $cData['course_language'];
			$_course['extLink'      ]['url' ] = $cData['department_url' ];
			$_course['extLink'      ]['name'] = $cData['department_name'];
			$_course['categoryCode' ]         = $cData['faCode'         ];
			$_course['categoryName' ]         = $cData['faName'         ];

			$_course['visibility'   ]        = $cData['visibility'      ];
			$_course['subscribe_allowed']    = $cData['subscribe'       ];
			$_course['unubscribe_allowed']   = $cData['unsubscribe'     ];
		}
		return $_course;
	}
	global $_course;
	return $_course;
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
		include_once api_get_path(LIBRARY_PATH).'session_handler.class.php';
		$session_handler = new session_handler();
		@session_set_save_handler(array(& $session_handler, 'open'), array(& $session_handler, 'close'), array(& $session_handler, 'read'), array(& $session_handler, 'write'), array(& $session_handler, 'destroy'), array(& $session_handler, 'garbage'));
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
	if (isset($GLOBALS[$variable])) {
		unset ($GLOBALS[$variable]);
	}
	if (isset($_SESSION[$variable])) {
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
function api_add_url_param($url, $param, $filter_xss = true) {
	if (empty($param)) {
		return $url;
	}
	if (strpos($url, '?') !== false) {
		if ($param[0] != '&') {
			$param = '&'.$param;
		}
		list (, $query_string) = explode('?', $url);
		$param_list1 = explode('&', $param);
		$param_list2 = explode('&', $query_string);
		$param_list1_keys = $param_list1_vals = array();
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
		$url = Security::remove_XSS(urldecode($url));
	}
	return $url;
}

// TODO: To be moved to the UserManager.
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

// TODO: To be moved to the UserManager.
// TODO: Multibyte support to be implemented.
/**
 * Checks a password to see wether it is OK to use.
 * @param string $password
 * @return true if the password is acceptable, false otherwise
 */
function api_check_password($password) {
	$length_pass = strlen($password);
	if ($length_pass < 5) {
		return false;
	}
	$pass_lower = strtolower($password);
	$nb_lettres = $nb_digits = 0;
	$nb_sequent_chars = 0;
	$char_code_previous = 0;
	for ($i = 0; $i < $length_pass; $i ++) {
		$char_code_current = ord($pass_lower[$i]);
		if ($i && abs($char_code_current - $char_code_previous) <= 1) {
			$nb_sequent_chars ++;
			if ($nb_sequent_chars == 3) {
				return false;
			}
		} else {
			$nb_sequent_chars = 1;
		}
		if ($char_code_current >= 97 && $char_code_current <= 122) {
			$nb_lettres ++;
		} elseif ($char_code_current >= 48 && $char_code_current <= 57) {
			$nb_digits ++;
		} else {
			return false;
		}
		$char_code_previous = $char_code_current;
	}
	return $nb_lettres >= 3 && $nb_digits >= 2;
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
function api_clear_anonymous($db_check = false) {
	global $_user;
	if (api_is_anonymous($_user['user_id'], $db_check)) {
		unset($_user['user_id']);
		api_session_unregister('_uid');
		return true;
	}
	return false;
}

// TODO: To be moved in text.lib.php
/**
 * Truncates a string.
 *
 * @author Brouckaert Olivier
 * @param  string $text					The text to truncate.
 * @param  integer $length				The approximate desired length. The length of the suffix below is to be added to have the total length of the result string.
 * @param  string $suffix				A suffix to be added as a replacement.
 * @param string $encoding (optional)	The encoding to be used. If it is omitted, the platform character set will be used by default.
 * @param  boolean $middle				If this parameter is true, truncation is done in the middle of the string.
 * @return string						Truncated string, decorated with the given suffix (replacement).
 */
function api_trunc_str($text, $length = 30, $suffix = '...', $middle = false, $encoding = null) {
	if (empty($encoding)) {
		$encoding = api_get_system_encoding();
	}
	$text_length = api_strlen($text, $encoding);
	if ($text_length <= $length) {
		return $text;
	}
	if ($middle) {
		return rtrim(api_substr($text, 0, round($length / 2), $encoding)).$suffix.ltrim(api_substr($text, - round($length / 2), $text_length, $encoding));
	}
	return rtrim(api_substr($text, 0, $length, $encoding)).$suffix;
}

// TODO: To be moved in text.lib.php
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

// TODO: There is a function api_get_status_langvars(). A combination is possible.
/**
 * Returns the status string corresponding to the status code
 * @author Noel Dieschburg
 * @param the int status code
 */
function get_status_from_code($status_code) {
	switch ($status_code) {
		case STUDENT:
			return get_lang('Student', '');
		case TEACHER:
			return get_lang('Teacher', '');
		case COURSEMANAGER:
			return get_lang('Manager', '');
		case SESSIONADMIN:
			return get_lang('SessionsAdmin', '');
		case DRH:
			return get_lang('Drh', '');
	}
}

/*
==============================================================================
		FAILURE MANAGEMENT
==============================================================================
*/

/**
 * The Failure Management module is here to compensate
 * the absence of an 'exception' device in PHP 4.
 */

/**
 * $api_failureList - array containing all the failure recorded
 * in order of arrival.
 */
$api_failureList = array();

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
	if (!empty($_user['user_id'])) {
		return false;
	}
	$user_id = api_get_anonymous_id();
	if ($user_id == 0) {
		return false;
	}
	api_session_unregister('_user');
	$_user['user_id'] = $user_id;
	$_user['is_anonymous'] = true;
	api_session_register('_user');
	$GLOBALS['_user'] = $_user;
	return true;
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
class api_failure {

	// TODO: $api_failureList to be hidden from global scope.
	/**
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
		if (count($api_failureList) == 0) { return ''; }
		return $api_failureList[count($api_failureList) - 1];
	}
}

/*
==============================================================================
		CONFIGURATION SETTINGS
==============================================================================
*/

/**
 * Gets the current Dokeos (not PHP/cookie) session ID, if active
 * @return  int     O if no active session, the session ID otherwise
 */
function api_get_session_id() {
	return empty($_SESSION['id_session']) ? 0 : (int)$_SESSION['id_session'];
}

/**
 * Gets the current or given session name
 * @param   int     Session ID (optional)
 * @return  string  The session name, or null if unfound
 */
function api_get_session_name($session_id) {
	if (empty($session_id)) {
		$session_id = api_get_session_id();
		if (empty($session_id)) { return null; }
	}
	$t = Database::get_main_table(TABLE_MAIN_SESSION);
	$s = "SELECT name FROM $t WHERE id = ".(int)$session_id;
	$r = Database::query($s, __FILE__, __LINE__);
	$c = Database::num_rows($r);
	if ($c > 0) {
		//technically, there can be only one, but anyway we take the first
		$rec = Database::fetch_array($r);
		return $rec['name'];
	}
	return null;
}

/**
 * Gets the session info by id
 * @param   int     Session ID
 * @return  array 	information of the session
 */
function api_get_session_info($session_id) {
	$data = array();
	if (!empty($session_id)) {
		$sesion_id = intval(Database::escape_string($session_id));
		$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
		$sql = "SELECT * FROM $tbl_session WHERE id = $session_id";
		$result = Database::query($sql,__FILE__,__LINE__);

	    if (Database::num_rows($result)>0) {
	    	$data = Database::fetch_array($result, 'ASSOC');
	    }
	}
	return $data;
}

/**
 * Gets the session visibility by session id
 * @param   int	session id
 * @return  int	0 = session still available, SESSION_VISIBLE_READ_ONLY = 1, SESSION_VISIBLE = 2, SESSION_INVISIBLE = 3
 */
function api_get_session_visibility($session_id) {
	$visibility = 0; //means that the session is still available
	if (!empty($session_id)) {
		$sesion_id = intval(Database::escape_string($session_id));
		$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

		$is_coach = api_is_course_coach();

		$condition_date_end = "";
		if ($is_coach) {
			$condition_date_end = " AND (CURDATE() > (SELECT adddate(date_end,nb_days_access_after_end) FROM $tbl_session WHERE id = $session_id) AND date_end != '0000-00-00') ";
		} else {
			$condition_date_end = " AND (date_end < CURDATE() AND date_end != '0000-00-00') ";
		}

		$sql = "SELECT visibility FROM $tbl_session
				WHERE id = $session_id $condition_date_end "; // session is old and is not unlimited

		$result = Database::query($sql,__FILE__,__LINE__);

	    if (Database::num_rows($result)>0) {
	    	$row = Database::fetch_array($result, 'ASSOC');
	    	$visibility = $row['visibility'];
	    } else {
	    	$visibility = 0;
	    }
	}
	return $visibility;
}
/**
 * Gets the visibility of an session of a course that a student have
 * @param   int     session id
 * @param   string  Dokeos course code
 * @param   int     user id
 * @return  int  	0= Session available (in date), SESSION_VISIBLE_READ_ONLY = 1, SESSION_VISIBLE = 2, SESSION_INVISIBLE = 3
 */
function api_get_session_visibility_by_user($session_id,$course_code, $user_id) {
	$visibility = 0; //means that the session is still available
	if (!empty($session_id) && !empty($user_id)){
		$sesion_id = intval(Database::escape_string($session_id));
		$user_id = intval(Database::escape_string($user_id));
		$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION_REL_COURSE_REL_USER);
		$sql = "SELECT visibility FROM $tbl_session
				WHERE id_session = $session_id AND id_user = $user_id AND course_code = '$course_code'"; // session old
		$result = Database::query($sql,__FILE__,__LINE__);
	    if (Database::num_rows($result)>0) {
	    	$row = Database::fetch_array($result, 'ASSOC');
	    	$visibility = $row['visibility'];
	    } else {
	    	$visibility = 0;
	    }
	}
	return $visibility;
}

/**
 * This function validates if the resource belongs to a session and returns an image
 * @param int	session id
 * @param int	status id
 * @return string	image session
 */
function api_get_session_image($session_id, $status_id) {
	//validacion when belongs to a session
	$session_id = (int)$session_id;
	$session_img = '';
	if ((int)$status_id != 5) { //check whether is not a student
		if ($session_id > 0) {
			$session_img = "&nbsp;".Display::return_icon('star.png', get_lang('ResourceSession'), array('align' => 'absmiddle'));
		}
	}
	return $session_img;
}

/**
 * This function add an additional condition according to the session of the course
 * @param int	session id
 * @param bool	optional, true if more than one condition false if the only condition in the query
 * @param bool	optional, true if condition is only with session_id = current session id, false if condition is with 0 else
 * @return string	condition of the session
 */
function api_get_session_condition($session_id, $state = true, $both = false) {

	$session_id = intval($session_id);

	//condition to show resources by session
	$condition_session = '';
	$condition_add = $state == false ? " WHERE " : " AND ";
	if ($session_id > 0) {
		if (api_is_session_in_category($session_id,'20091U') || $both) {
			$condition_session = $condition_add . " ( session_id = ".(int)$session_id." OR session_id = 0 ) ";
		} else {
			$condition_session = $condition_add . "  session_id = ".(int)$session_id." ";
		}
	} else {
		$condition_session = $condition_add . " session_id = 0 ";
	}
	return $condition_session;

}

/**
 * This function returns information about coachs from a course in session
 * @param int	- optional, session id
 * @param string - optional, course code
 * @return array  -	array containing user_id, lastname, firstname, username.
 */
function api_get_coachs_from_course($session_id=0,$course_code='') {

	if (!empty($session_id)) {
		$session_id = intval($session_id);
	} else {
		$session_id = api_get_session_id();
	}

	if (!empty($course_code)) {
		$course_code = Database::escape_string($course_code);
	} else {
		$course_code = api_get_course_id();
	}

	$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
	$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$coachs = array();

	$sql = "SELECT u.user_id,u.lastname,u.firstname,u.username FROM $tbl_user u,$tbl_session_course_user scu
			WHERE u.user_id = scu.id_user AND scu.id_session = '$session_id' AND scu.course_code = '$course_code' AND scu.status = 2";
	$rs = Database::query($sql,__FILE__,__LINE__);

	if (Database::num_rows($rs) > 0) {

		while ($row = Database::fetch_array($rs)) {
			$coachs[] = $row;
		}
		return $coachs;
	} else {
		return false;
	}
}

/**
 * Returns the value of a setting from the web-adjustable admin config settings.
 *
 * WARNING true/false are stored as string, so when comparing you need to check e.g.
 * if (api_get_setting('show_navigation_menu') == 'true') //CORRECT
 * instead of
 * if (api_get_setting('show_navigation_menu') == true) //INCORRECT
 * @param	string	The variable name
 * @param	string	The subkey (sub-variable) if any. Defaults to NULL
 * @author René Haentjens
 * @author Bart Mollet
 */
function api_get_setting($variable, $key = null) {
	global $_setting;
	return is_null($key) ? (!empty($_setting[$variable]) ? $_setting[$variable] : null) : $_setting[$variable][$key];
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
	if ($_SESSION['is_platformAdmin']) {
		return true;
	}
	global $_user;
	return $allow_sessions_admins && $_user['status'] == SESSIONADMIN;
}

/**
 * Check if current user is allowed to create courses
 * @return boolean True if the user has course creation rights,
 * false otherwise.
 */
function api_is_allowed_to_create_course() {
	return $_SESSION['is_allowedCreateCourse'];
}

/**
 * Check if the current user is a course administrator
 * @return boolean True if current user is a course administrator
 */
function api_is_course_admin() {
	return $_SESSION['is_courseAdmin'];
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
 * @params int - optional, session id
 * @params string - optional, course code
 * @return boolean True if current user is a course or session coach
 */
function api_is_coach($session_id = 0, $course_code = '') {
	global $_user;
	global $sessionIsCoach;

	if (!empty($session_id)) {
		$session_id = intval($session_id);
	} else {
		$session_id = api_get_session_id();
	}

	if (!empty($course_code)) {
		$course_code = Database::escape_string($course_code);
	} else {
		$course_code = api_get_course_id();
	}

	$sql = "SELECT DISTINCT id, name, date_start, date_end
							FROM session
							INNER JOIN session_rel_course_rel_user session_rc_ru
								ON session_rc_ru.id_user = '".Database::escape_string($_user['user_id'])."'
							WHERE session_rc_ru.course_code = '$course_code' AND session_rc_ru.status = 2 AND session_rc_ru.id_session = '$session_id'
							ORDER BY date_start, date_end, name";

	$result = Database::query($sql,__FILE__,__LINE__);
	$sessionIsCoach = Database::store_result($result);

	$sql = "SELECT DISTINCT id, name, date_start, date_end
							FROM session
							WHERE session.id_coach =  '".Database::escape_string($_user['user_id'])."'
							AND id = '$session_id'
							ORDER BY date_start, date_end, name";

	$result = Database::query($sql,__FILE__,__LINE__);
	$sessionIsCoach = array_merge($sessionIsCoach , Database::store_result($result));

	return (count($sessionIsCoach) > 0);

}

/**
 * Check if the current user is a session administrator
 * @return boolean True if current user is a course administrator
 */
function api_is_session_admin() {
	global $_user;
	return $_user['status'] == SESSIONADMIN;
}

/**
 * This function check is a session is assigned into a category
 * @param int	- session id
 * @param string - category name
 * @return bool  -	true if is found, otherwise false
 */
function api_is_session_in_category($session_id,$category_name) {

	$session_id = intval($session_id);
	$category_name = Database::escape_string($category_name);

	$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

	$sql = "select 1 FROM $tbl_session WHERE $session_id IN (SELECT s.id FROM $tbl_session s, $tbl_session_category sc  WHERE s.session_category_id = sc.id AND sc.name LIKE '%$category_name' )";
	$rs = Database::query($sql,__FILE__,__LINE__);

	if (Database::num_rows($rs) > 0) {
		return true;
	} else {
		return false;
	}

}

/*
==============================================================================
		DISPLAY OPTIONS
		student view, title, message boxes,...
==============================================================================
*/

// TODO: To be moved to Display class.
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
 * @param  mixed $title_element - it could either be a string or an array
 *                               containing 'supraTitle', 'mainTitle',
 *                               'subTitle'
 * @return void
 */
function api_display_tool_title($title_element) {
	if (is_string($title_element)) {
		$tit = $title_element;
		unset ($title_element);
		$title_element['mainTitle'] = $tit;
	}
	echo '<h3>';
	if (!empty($title_element['supraTitle'])) {
		echo '<small>'.$title_element['supraTitle'].'</small><br />';
	}
	if (!empty($title_element['mainTitle'])) {
		echo $title_element['mainTitle'];
	}
	if (!empty($title_element['subTitle'])) {
		echo '<br /><small>'.$title_element['subTitle'].'</small>';
	}
	echo '</h3>';
}

// TODO: To be moved to Display class.
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
 *	@author Roan Embrechts
 *	@author Patrick Cool
 *	@version 1.2
 *	@todo rewrite code so it is easier to understand
 */
function api_display_tool_view_option() {

	if (api_get_setting('student_view_enabled') != 'true') {
		return '';
	}

	$output_string = '';

	$sourceurl = '';
	$is_framed = false;
	// Exceptions apply for all multi-frames pages
	if (strpos($_SERVER['REQUEST_URI'], 'chat/chat_banner.php') !== false) {	//the chat is a multiframe bit that doesn't work too well with the student_view, so do not show the link
		$is_framed = true;
		return '';
	}

	// Uncomment to remove student view link from document view page
	if (strpos($_SERVER['REQUEST_URI'], 'document/headerpage.php') !== false) {
		$sourceurl = str_replace('document/headerpage.php', 'document/showinframes.php', $_SERVER['REQUEST_URI']);
		//showinframes doesn't handle student view anyway...
		//return '';
		$is_framed = true;
	}

	// Uncomment to remove student view link from document view page
	if (strpos($_SERVER['REQUEST_URI'], 'newscorm/lp_header.php') !== false) {
		if (empty($_GET['lp_id'])) {
			return '';
		}
		$sourceurl = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
		$sourceurl = str_replace('newscorm/lp_header.php', 'newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.intval($_GET['lp_id']).'&isStudentView='.($_SESSION['studentview']=='studentview' ? 'false' : 'true'), $sourceurl);
		//showinframes doesn't handle student view anyway...
		//return '';
		$is_framed = true;
	}

	// check if the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
	if (!$is_framed) {
		if (strpos($_SERVER['REQUEST_URI'], '?') === false) {
			$sourceurl = api_get_self().'?'.api_get_cidreq();
		} else {
			$sourceurl = $_SERVER['REQUEST_URI'];
			//$sourceurl = str_replace('&', '&amp;', $sourceurl);
		}
	}
	if (!empty($_SESSION['studentview'])) {
		if ($_SESSION['studentview'] == 'studentview') {
			// we have to remove the isStudentView=true from the $sourceurl
			$sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
			$sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
			$output_string .= '<a href="'.$sourceurl.'&isStudentView=false" target="_self">'.get_lang('CourseManagerview').'</a>';
		} elseif ($_SESSION['studentview'] == 'teacherview') {
			//switching to teacherview
			$sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
			$sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
			$output_string .= '<a href="'.$sourceurl.'&isStudentView=true" target="_self">'.get_lang('StudentView').'</a>';
		}
	} else {
		$output_string .= '<a href="'.$sourceurl.'&isStudentView=true" target="_self">'.get_lang('StudentView').'</a>';
	}
	echo $output_string;
}

/**
 * Displays the contents of an array in a messagebox.
 * @param array $info_array An array with the messages to show
 */
function api_display_array($info_array) {
	foreach ($info_array as $element) {
		$message .= $element.'<br />';
	}
	Display :: display_normal_message($message);
}

/**
 *	Displays debug info
 *	@param string $debug_info The message to display
 *	@author Roan Embrechts
 *	@version 1.1, March 2004
 */
function api_display_debug_info($debug_info) {
	$message = '<i>Debug info</i><br />';
	$message .= $debug_info;
	Display :: display_normal_message($message);
}

// TODO: This is for the permission section.
/**
*	Function that removes the need to directly use is_courseAdmin global in
*	tool scripts. It returns true or false depending on the user's rights in
*	this particular course.
*	Optionally checking for tutor and coach roles here allows us to use the
*	student_view feature altogether with these roles as well.
*	@param	bool	Whether to check if the user has the tutor role
*	@param	bool	Whether to check if the user has the coach role
*	@param	bool	Whether to check if the user has the session coach role
*
*	@author Roan Embrechts
*	@author Patrick Cool
*	@version 1.1, February 2004
*	@return boolean, true: the user has the rights to edit, false: he does not
*/

function api_is_allowed_to_edit($tutor=false,$coach=false,$session_coach = false) {

	$is_courseAdmin = api_is_course_admin() || api_is_platform_admin();
	if (!$is_courseAdmin && $tutor == true) {	//if we also want to check if the user is a tutor...
		$is_courseAdmin = $is_courseAdmin || api_is_course_tutor();
	}
	if (!$is_courseAdmin && $coach == true) {	//if we also want to check if the user is a coach...';

		// check if session visibility is read only for coachs
		$is_allowed_coach_to_edit = api_is_course_coach();
		$my_session_id = api_get_session_id();
		$session_visibility = api_get_session_visibility($my_session_id);
		if ($session_visibility==SESSION_VISIBLE_READ_ONLY) {
			$is_allowed_coach_to_edit = false;
		}

		if (api_get_setting('allow_coach_to_edit_course_session') == 'true') { // check if coach is allowed to edit a course
			$is_courseAdmin = $is_courseAdmin || $is_allowed_coach_to_edit;
		} else {
			$is_courseAdmin = $is_courseAdmin;
		}

	}
	if (!$is_courseAdmin && $session_coach == true) {
		$is_courseAdmin = $is_courseAdmin || api_is_coach();
	}

	if (api_get_setting('student_view_enabled') == 'true') {	//check if the student_view is enabled, and if so, if it is activated
		$is_allowed = $is_courseAdmin && $_SESSION['studentview'] != "studentview";
		return $is_allowed;
	} else {
		return $is_courseAdmin;
	}
}

/**
* Checks if a student can edit contents in a session depending
* on the session visibility
* @param	bool	Whether to check if the user has the tutor role
* @param	bool	Whether to check if the user has the coach role
* @return boolean, true: the user has the rights to edit, false: he does not
*/
function api_is_allowed_to_session_edit($tutor=false,$coach=false) {
	if (api_is_allowed_to_edit($tutor, $coach)) {
		// if I'm a teacher, I will return true in order to not affect the normal behaviour of Dokeos tools
		return true;
	} else {
		if (api_get_session_id()==0) {
			// i'm not in a session so i will return true to not affect the normal behaviour of Dokeos tools
			return true;
		} else {
			//I'm in a session and I'm a student
			$session_id= api_get_session_id();
			// Get the session visibility
			$session_visibility = api_get_session_visibility($session_id);	//if 0 the session is still available
			if ($session_visibility!=0) {
				//@todo we could load the session_rel_course_rel_user permission to increase the level of detail
				//echo api_get_user_id();
				//echo api_get_course_id();

				switch($session_visibility) {
					case SESSION_VISIBLE_READ_ONLY: //1
					return false;
					break;
					case SESSION_VISIBLE:			//2
					return true;
					break;
					case SESSION_INVISIBLE:			//3
					return false;
					break;
				}
			} else {
				return true;
			}
		}
	}
}

/**
* Checks whether the user is allowed in a specific tool for a specific action
* @param $tool the tool we are checking if the user has a certain permission
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
function api_is_anonymous($user_id = null, $db_check = false) {
	if (!isset($user_id)) {
		$user_id = api_get_user_id();
	}
	if ($db_check) {
		$info = api_get_user_info($user_id);
		if ($info['status'] == 6) {
			return true;
		}
	}
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
	return isset($_user['is_anonymous']) && $_user['is_anonymous'] === true;
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
	global $this_section;

	$origin = isset($_GET['origin']) ? $_GET['origin'] : '';

	if ($origin == 'learnpath') {
		echo '
				<style type="text/css" media="screen, projection">
				/*<![CDATA[*/
				@import "'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css";
				/*]]>*/
				</style>';
	}

	if ((isset($user) && !api_is_anonymous()) && (!isset($course) || $course == -1) && empty($_GET['cidReq'])) {
		//if the access is not authorized and there is some login information
		// but the cidReq is not found, assume we are missing course data and send the user
		// to the user_portal
		if ((!headers_sent() or $print_headers) && $origin != 'learnpath') { Display::display_header(''); }
		echo '<div align="center">';
		Display::display_error_message(get_lang('NotAllowedClickBack').'<br /><br /><a href="'.$_SERVER['HTTP_REFERER'].'">'.get_lang('BackToPreviousPage').'</a><br />', false);
		echo '</div>';
		if ($print_headers && $origin != 'learnpath') { Display::display_footer(); }
		die();
	}
	if (!empty($_SERVER['REQUEST_URI']) && (!empty($_GET['cidReq']) || $this_section == SECTION_MYPROFILE)) {
		//only display form and return to the previous URL if there was a course ID included
		if (!empty($user) && !api_is_anonymous()) {
			if ((!headers_sent() || $print_headers) && $origin != 'learnpath') { Display::display_header(''); }
			echo '<div align="center">';
			Display::display_error_message(get_lang('NotAllowedClickBack').'<br /><br /><a href="'.$_SERVER['HTTP_REFERER'].'">'.get_lang('BackToPreviousPage').'</a><br />', false);
			echo '</div>';
			if ($print_headers && $origin != 'learnpath') { Display::display_footer(); }
			die();
		}
		include_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
		$form = new FormValidator('formLogin', 'post', api_get_self().'?'.$_SERVER['QUERY_STRING']);
		$form->addElement('static', null, null, 'Username');
		$form->addElement('text', 'login', '', array('size' => USERNAME_MAX_LENGTH));
		$form->addElement('static', null, null, 'Password');
		$form->addElement('password', 'password', '', array('size' => 15));
		$form->addElement('style_submit_button', 'submitAuth', get_lang('Enter'),'class="login"');
		$test ='<div id="expire_session"><br />'.$form->return_form().'</div>';

		if ((!headers_sent() || $print_headers) && $origin != 'learnpath') { Display::display_header(''); }
		Display::display_error_message('<left>'.get_lang('NotAllowed').'<br />'.get_lang('PleaseLoginAgainFromFormBelow').'<br />'.$test.'</left>', false);
		$_SESSION['request_uri'] = $_SERVER['REQUEST_URI'];
		if ($print_headers && $origin != 'learnpath') { Display::display_footer(); }
		die();
	}
	if (!empty($user) && !api_is_anonymous()) {
		if ((!headers_sent() or $print_headers) && $origin != 'learnpath') { Display::display_header(''); }
		echo '<div align="center">';
		Display::display_error_message(get_lang('NotAllowedClickBack').'<br /><br /><a href="'.$_SERVER['HTTP_REFERER'].'">'.get_lang('BackToPreviousPage').'</a><br />', false);
		echo '</div>';
		if ($print_headers && $origin != 'learnpath') {Display::display_footer();}
		die();
	}
	//if no course ID was included in the requested URL, redirect to homepage
	if ($print_headers && $origin != 'learnpath') { Display::display_header(''); }
	echo '<div align="center">';
	Display::display_error_message(get_lang('NotAllowed').'<br /><br /><a href="'.$home_url.'">'.get_lang('PleaseLoginAgainFromHomepage').'</a><br />', false);
	echo '</div>';
	if ($print_headers && $origin != 'learnpath') { Display::display_footer(); }
	die();
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
	list ($last_post_date, $last_post_time) = split(' ', $last_post_datetime);
	list ($year, $month, $day) = explode('-', $last_post_date);
	list ($hour, $min, $sec) = explode(':', $last_post_time);
	return mktime((int)$hour, (int)$min, (int)$sec, (int)$month, (int)$day, (int)$year);
}

/**
 * Gets a MySQL datetime format string from a UNIX timestamp
 * @param   int     UNIX timestamp, as generated by the time() function. Will be generated if parameter not provided
 * @return  string  MySQL datetime format, like '2009-01-30 12:23:34'
 */
function api_get_datetime($time = null) {
	if (!isset($time)) { $time = time(); }
	return date('Y-m-d H:i:s', $time);
}

/**
 * Gets item visibility from the item_property table
 * @param	array	Course properties array (result of api_get_course_info())
 * @param	string	Tool (learnpath, document, etc)
 * @param	int		The item ID in the given tool
 * @return	int		-1 on error, 0 if invisible, 1 if visible
 */
function api_get_item_visibility($_course, $tool, $id) {
	if (!is_array($_course) || count($_course) == 0 || empty($tool) || empty($id)) { return -1; }
	$tool = Database::escape_string($tool);
	$id = Database::escape_string($id);
	$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY, $_course['dbName']);
	$sql = "SELECT visibility FROM $TABLE_ITEMPROPERTY WHERE tool = '$tool' AND ref = $id";
	$res = Database::query($sql, __FILE__, __LINE__);
	if ($res === false || Database::num_rows($res) == 0) { return -1; }
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
function api_item_property_update($_course, $tool, $item_id, $lastedit_type, $user_id, $to_group_id = 0, $to_user_id = NULL, $start_visible = 0, $end_visible = 0, $session_id = 0) {

	// definition of variables
	$tool = Database::escape_string($tool);
	$item_id = Database::escape_string($item_id);
	$lastedit_type = Database::escape_string($lastedit_type);
	$user_id = Database::escape_string($user_id);
	$to_group_id = Database::escape_string($to_group_id);
	$to_user_id = Database::escape_string($to_user_id);
	$start_visible = Database::escape_string($start_visible);
	$end_visible = Database::escape_string($end_visible);
	$start_visible = ($start_visible == 0) ? "0000-00-00 00:00:00" : $start_visible;
	$end_visible = ($end_visible == 0) ? "0000-00-00 00:00:00" : $end_visible;
	$to_filter = "";
	$time = time();
	$time = date("Y-m-d H:i:s", $time);
	if (!empty($session_id)) {
		$session_id = intval($session_id);
	}

	// Definition of tables
	$TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY,$_course['dbName']);

	if ($to_user_id <= 0) {
		$to_user_id = NULL; //no to_user_id set
	}

	if (!is_null($to_user_id)) {
		// $to_user_id has more priority than $to_group_id
		$to_field = "to_user_id";
		$to_value = $to_user_id;
	} else {
		// $to_user_id is not set
		$to_field = "to_group_id";
		$to_value = $to_group_id;
	}

	// set filters for $to_user_id and $to_group_id, with priority for $to_user_id

	$condition_session = "";
	if (!empty($session_id)) {
		$condition_session = " AND id_session = '$session_id' ";
	}

	$filter = "tool='$tool' AND ref='$item_id' $condition_session ";

	if ($item_id == "*") {
		$filter = "tool='$tool' AND visibility<>'2' $condition_session"; // for all (not deleted) items of the tool
	}
	// check if $to_user_id and $to_group_id are passed in the function call
	// if both are not passed (both are null) then it is a message for everybody and $to_group_id should be 0 !
	if (is_null($to_user_id) && is_null($to_group_id)) {
		$to_group_id = 0;
	}
	if (!is_null($to_user_id)) {
		$to_filter = " AND to_user_id='$to_user_id' $condition_session"; // set filter to intended user
	} else {
		if (($to_group_id != 0) && $to_group_id == strval(intval($to_group_id))) {
			$to_filter = " AND to_group_id='$to_group_id' $condition_session"; // set filter to intended group
		}
	}
	// update if possible
	$set_type = "";
	switch ($lastedit_type) {
		case "delete" : // delete = make item only visible for the platform admin
			$visibility = '2';

			if (!empty($session_id)) {

				// check if session id already exist into itemp_properties for updating visibility or add it
				$sql = "select id_session FROM $TABLE_ITEMPROPERTY WHERE tool = '$tool' AND ref='$item_id' AND id_session = '$session_id'";
				$rs = Database::query($sql,__FILE__,__LINE__);
				if (Database::num_rows($rs) > 0) {
					$sql = "UPDATE $TABLE_ITEMPROPERTY
							SET lastedit_type='".str_replace('_', '', ucwords($tool))."Deleted', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility', id_session = '$session_id' $set_type
							WHERE $filter";
				} else {

					$sql = "INSERT INTO $TABLE_ITEMPROPERTY
						   		  			(tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, id_session)
						         	VALUES 	('$tool','$item_id','$time', '$user_id', '$time',	'$lastedit_type','$user_id', '$to_value', '$visibility', '$start_visible','$end_visible', '$session_id')";
				}

			}	else {
				$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_type='".str_replace('_', '', ucwords($tool))."Deleted', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
										WHERE $filter";
			}

			break;
		case "visible" : // change item to visible
			$visibility = '1';

			if (!empty($session_id)) {

				// check if session id already exist into itemp_properties for updating visibility or add it
				$sql = "select id_session FROM $TABLE_ITEMPROPERTY WHERE tool = '$tool' AND ref='$item_id' AND id_session = '$session_id'";
				$rs = Database::query($sql,__FILE__,__LINE__);
				if (Database::num_rows($rs) > 0) {
					$sql = "UPDATE $TABLE_ITEMPROPERTY
							SET lastedit_type='".str_replace('_', '', ucwords($tool))."Visible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility', id_session = '$session_id' $set_type
							WHERE $filter";
				} else {

					$sql = "INSERT INTO $TABLE_ITEMPROPERTY
						   		  			(tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, id_session)
						         	VALUES 	('$tool','$item_id','$time', '$user_id', '$time',	'$lastedit_type','$user_id', '$to_value', '$visibility', '$start_visible','$end_visible', '$session_id')";
				}

			}	else {
				$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_type='".str_replace('_', '', ucwords($tool))."Visible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
										WHERE $filter";
			}

			break;
		case "invisible" : // change item to invisible
			$visibility = '0';
			if (!empty($session_id)) {
				// check if session id already exist into itemp_properties for updating visibility or add it
				$sql = "Select id_session FROM $TABLE_ITEMPROPERTY WHERE tool = '$tool' AND ref='$item_id' AND id_session = '$session_id'";
				$rs = Database::query($sql,__FILE__,__LINE__);
				if (Database::num_rows($rs) > 0) {
					$sql = "UPDATE $TABLE_ITEMPROPERTY
							SET lastedit_type='".str_replace('_', '', ucwords($tool))."Invisible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility', id_session = '$session_id' $set_type
							WHERE $filter";
				} else {

					$sql = "INSERT INTO $TABLE_ITEMPROPERTY
						   		  			(tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, id_session)
						         	VALUES 	('$tool','$item_id','$time', '$user_id', '$time',	'$lastedit_type','$user_id', '$to_value', '$visibility', '$start_visible','$end_visible', '$session_id')";
				}

			} else {
				$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_type='".str_replace('_', '', ucwords($tool))."Invisible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
										WHERE $filter";
			}

			break;
		default : // item will be added or updated
			$set_type = ", lastedit_type='$lastedit_type' ";
			$visibility = '1';
			$filter .= $to_filter;
			$sql = "UPDATE $TABLE_ITEMPROPERTY
										SET lastedit_date='$time', lastedit_user_id='$user_id' $set_type
										WHERE $filter";
	}

	$res = Database::query($sql, __FILE__, __LINE__);
	// insert if no entries are found (can only happen in case of $lastedit_type switch is 'default')
	if (Database::affected_rows() == 0) {

		$sql = "INSERT INTO $TABLE_ITEMPROPERTY
						   		  			(tool,ref,insert_date,insert_user_id,lastedit_date,lastedit_type,   lastedit_user_id,$to_field,  visibility,   start_visible,   end_visible, id_session)
						         	VALUES 	('$tool','$item_id','$time',    '$user_id',	   '$time',		 '$lastedit_type','$user_id',	   '$to_value','$visibility','$start_visible','$end_visible', '$session_id')";
		$res = Database::query($sql, __FILE__, __LINE__);
		if (!$res) {
			return false;
		}
	}
	return true;
}

/*
==============================================================================
		Language Dropdown
==============================================================================
*/

// TODO: To be moved to Display class.
/**
 *	Displays a combobox so the user can select his/her preferred language.
 *   @param string The desired name= value for the select
 *   @return string
 */

function api_get_languages_combo($name = 'language') {

	$ret = '';

	$platformLanguage = api_get_setting('platformLanguage');

	/* retrieve a complete list of all the languages. */
	$language_list = api_get_languages();

	if (count($language_list['name']) < 2) {
		return $ret;
	}

	/*	the the current language of the user so that his/her language occurs as
		selected in the dropdown menu */
	if (isset($_SESSION['user_language_choice'])) {
		$default = $_SESSION['user_language_choice'];
	} else {
		$default = $platformLanguage;
	}

	$languages = $language_list['name'];
	$folder = $language_list['folder'];

	$ret .= '<select name="'.$name.'">';
	foreach ($languages as $key => $value) {
		if ($folder[$key] == $default) {
			$selected = ' selected="selected"';
		} else {
			$selected = '';
		}
		$ret .= sprintf('<option value=%s" %s>%s</option>'."\n", $folder[$key], $selected, $value);
	}
	$ret .= '</select>';

	return $ret;
}

// TODO: To be moved to Display calss.
/**
 *	Displays a form (drop down menu) so the user can select his/her preferred language.
 *	The form works with or without javascript
 *   @param  boolean Hide form if only one language available (defaults to false = show the box anyway)
 *   @return void Display the box directly
 */
function api_display_language_form($hide_if_no_choice = false) {
	$platformLanguage = api_get_setting('platformLanguage');
	$dirname = api_get_path(SYS_PATH).'main/lang/'; // TODO: this line is probably no longer needed
	// retrieve a complete list of all the languages.
	$language_list = api_get_languages();
	if (count($language_list['name']) <= 1 && $hide_if_no_choice) {
		return; //don't show any form
	}
	// the the current language of the user so that his/her language occurs as selected in the dropdown menu
	if (isset($_SESSION['user_language_choice'])) {
		$user_selected_language = $_SESSION['user_language_choice'];
	}
	if (!isset($user_selected_language)) {
		$user_selected_language = $platformLanguage;
	}
	$original_languages = $language_list['name'];
	$folder = $language_list['folder']; // this line is probably no longer needed
?>
	<script type="text/javascript">
	<!--
	function jumpMenu(targ,selObj,restore){ //v3.0
		eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
		if (restore) selObj.selectedIndex=0;
	}
	//-->
	</script>
<?php
	echo "<form id=\"lang_form\" name=\"lang_form\" method=\"post\" action=\"".api_get_self()."\">", "<select name=\"language_list\" onchange=\"javascript: jumpMenu('parent',this,0);\">";
	foreach ($original_languages as $key => $value) {
		if ($folder[$key] == $user_selected_language) {
			$option_end = " selected=\"selected\" >";
		} else {
			$option_end = ">";
		}
		echo "<option value=\"".api_get_self()."?language=".$folder[$key]."\"$option_end";
		#echo substr($value,0,16); #cut string to keep 800x600 aspect
		echo $value;
		echo "</option>\n";
	}
	echo "</select>";
	echo "<noscript><input type=\"submit\" name=\"user_select_language\" value=\"".get_lang("Ok")."\" /></noscript>";
	echo "</form>";
}

// TODO: Tobe moved in the Internationalization library.
/**
 * Return a list of all the languages that are made available by the admin.
 * @return array An array with all languages. Structure of the array is
 *  array['name'] = An array with the name of every language
 *  array['folder'] = An array with the corresponding dokeos-folder
 */
function api_get_languages() {
	$tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
	$sql = "SELECT * FROM $tbl_language WHERE available='1' ORDER BY original_name ASC";
	$result = Database::query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
		$language_list['name'][] = $row['original_name'];
		$language_list['folder'][] = $row['dokeos_folder'];
	}
	return $language_list;
}

/**
 * Return the id (the database id) of a language
 * @param string language name (dokeos_folder)
 * @return int id of the language
 */
function api_get_language_id($language) {
	$tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
	$language = Database::escape_string($language);
	$sql = "SELECT id FROM $tbl_language WHERE available='1' AND dokeos_folder = '$language' ORDER BY dokeos_folder ASC";
	$result = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_array($result);
	return $row['id'];
}

/**
 * Returns a list of CSS themes currently available in the CSS folder
 * @return	array	List of themes directories from the css folder
 * Note: Directory names (names of themes) in the file system should contain ASCII-characters only.
 */
function api_get_themes() {
	$cssdir = api_get_path(SYS_PATH).'main/css/';
	$list_dir = array();
	$list_name = array();

	if (@is_dir($cssdir)) {
		$themes = @scandir($cssdir);

		if (is_array($themes)) {
			if ($themes !== false) {
				sort($themes);

				foreach ($themes as $theme) {
					if (substr($theme, 0, 1) == '.') {
						//ignore
						continue;
					} else {
						if (@is_dir($cssdir.$theme)) {
							$list_dir[] = $theme;
							$list_name[] = ucwords(str_replace('_', ' ', $theme));
						}
					}
				}
			}
		}
	}
	$return = array();
	$return[] = $list_dir;
	$return[] = $list_name;
	return $return;
}

/*
==============================================================================
		WYSIWYG EDITOR
		functions for the WYSIWYG html editor, TeX parsing...
==============================================================================
*/

// TODO: A note to be placed (plus some justification): Preferable way to put an editor's instance on a page is through direct using the FormValidator class method.
// TODO: To be simplified, code from api_return_html_area() to be reused.
/**
 * Displays the FckEditor WYSIWYG editor for online editing of html
 * @param string $name The name of the form-element
 * @param string $content The default content of the html-editor
 * @param int $height The height of the form element
 * @param int $width The width of the form element
 * @param string $attributes (optional) attributes for the form element
 * @param array $editor_config (optional) Configuration options for the html-editor
 */
function api_disp_html_area($name, $content = '', $height = '', $width = '100%', $attributes = null, $editor_config = null) {
	global $_configuration, $_course, $fck_attribute;
	require_once dirname(__FILE__).'/formvalidator/Element/html_editor.php';
	$editor = new HTML_QuickForm_html_editor($name, null, $attributes, $editor_config);
	$editor->setValue($content);

	// The global variable $fck_attribute has been deprecated. It stays here for supporting old external code.
	if( $height != '') {
		$fck_attribute['Height'] = $height;
	}
	if( $width != '') {
		$fck_attribute['Width'] = $width;
	}

	echo $editor->toHtml();
}

function api_return_html_area($name, $content = '', $height = '', $width = '100%', $attributes = null, $editor_config = null) {
	global $_configuration, $_course, $fck_attribute;
	require_once(dirname(__FILE__).'/formvalidator/Element/html_editor.php');
	$editor = new HTML_QuickForm_html_editor($name, null, $attributes, $editor_config);
	$editor->setValue($content);

	// The global variable $fck_attribute has been deprecated. It stays here for supporting old external code.
	if ($height != '') {
		$fck_attribute['Height'] = $height;
	}
	if ($width != '') {
		$fck_attribute['Width'] = $width;
	}

	return $editor->toHtml();
}

/**
 * Send an email.
 *
 * Wrapper function for the standard php mail() function. Change this function
 * to your needs. The parameters must follow the same rules as the standard php
 * mail() function. Please look at the documentation on http://php.net/manual/en/function.mail.php
 * @param string $to
 * @param string $subject
 * @param string $message
 * @param string $additional_headers
 * @param string $additional_parameters
 * @author Ivan Tcholakov, 04-OCT-2009, a reworked version of this function.
 * @link http://www.dokeos.com/forum/viewtopic.php?t=15557
 */
function api_send_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null) {
	//return mail($to, $subject, $message, $additional_headers, $additional_parameters);

	require_once api_get_path(LIBRARY_PATH).'phpmailer/class.phpmailer.php';
	require_once api_get_path(CONFIGURATION_PATH).'mail.conf.php';

	if (empty($platform_email['SMTP_FROM_NAME'])) {
		$platform_email['SMTP_FROM_NAME'] = api_get_setting('administratorName').' '.api_get_setting('administratorSurname');
	}

	if (empty($platform_email['SMTP_FROM_EMAIL'])) {
		$platform_email['SMTP_FROM_EMAIL'] = api_get_setting('emailAdministrator');
	}

	$matches = array();
	if (preg_match('/([^<]*)<(.+)>/si', $to, $matches)) {
		$recipient_name = trim($matches[1]);
		$recipient_email = trim($matches[2]);
	} else {
		$recipient_name = '';
		$recipient_email = trim($to);
	}

	$sender_name = '';
	$sender_email = '';
	$extra_headers = $additional_headers;

	//regular expression to test for valid email address
	// this should actually be revised to use the complete RFC3696 description
	// http://tools.ietf.org/html/rfc3696#section-3
	$regexp = "^[0-9a-z_\.+-]+@(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,3})$";

	$mail = new PHPMailer();
	$mail->CharSet = api_get_system_encoding();
	$mail->Mailer = $platform_email['SMTP_MAILER'];
	$mail->Host = $platform_email['SMTP_HOST'];
	$mail->Port = $platform_email['SMTP_PORT'];

	if ($platform_email['SMTP_AUTH']) {
		$mail->SMTPAuth = 1;
		$mail->Username = $platform_email['SMTP_USER'];
		$mail->Password = $platform_email['SMTP_PASS'];
	}

	$mail->Priority = 3; // 5=low, 1=high
	$mail->AddCustomHeader('Errors-To: '.$platform_email['SMTP_FROM_EMAIL']);
	$mail->IsHTML(0);
	$mail->SMTPKeepAlive = true;

	// attachments
	// $mail->AddAttachment($path);
	// $mail->AddAttachment($path, $filename);

	if ($sender_email != '') {
		$mail->From = $sender_email;
		$mail->Sender = $sender_email;
		//$mail->ConfirmReadingTo = $sender_email; //Disposition-Notification
	} else {
		$mail->From = $platform_email['SMTP_FROM_EMAIL'];
		$mail->Sender = $platform_email['SMTP_FROM_EMAIL'];
		//$mail->ConfirmReadingTo = $platform_email['SMTP_FROM_EMAIL']; //Disposition-Notification
	}

	if ($sender_name != '') {
		$mail->FromName = $sender_name;
	} else {
		$mail->FromName = $platform_email['SMTP_FROM_NAME'];
	}
	$mail->Subject = $subject;
	$mail->Body = $message;
	//only valid address
	if (eregi( $regexp, $recipient_email )) {
		$mail->AddAddress($recipient_email, $recipient_name);
	}

	if ($extra_headers != '') {
		$mail->AddCustomHeader($extra_headers);
	}

	//send mail
	if (!$mail->Send()) {
		return 0;
	}

	// Clear all addresses
	$mail->ClearAddresses();
	return 1;
}

/**
 * Find the largest sort value in a given user_course_category
 * This function is used when we are moving a course to a different category
 * and also when a user subscribes to courses (the new course is added at the end of the main category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $user_course_category: the id of the user_course_category
 * @return int the value of the highest sort of the user_course_category
 */
function api_max_sort_value($user_course_category, $user_id) {
	$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$sql_max = "SELECT max(sort) as max_sort FROM $tbl_course_user WHERE user_id='".$user_id."' AND user_course_cat='".$user_course_category."'";
	$result_max = Database::query($sql_max, __FILE__, __LINE__);
	if (Database::num_rows($result_max) == 1) {
		$row_max = Database::fetch_array($result_max);
		return $row_max['max_sort'];
	}
	return 0;
}

/**
 * This function converts the string "true" or "false" to a boolean true or false.
 * This function is in the first place written for the Dokeos Config Settings (also named AWACS)
 * @param string "true" or "false"
 * @return boolean true or false
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function string_2_boolean($string) {
	if ($string == 'true') {
		return true;
	}
	if ($string == 'false') {
		return false;
	}
	// TODO: Here the function returns null implicitly. This case to be checked.
}

/**
 * Determines the number of plugins installed for a given location
 */
function api_number_of_plugins($location) {
	global $_plugins;
	return isset($_plugins[$location]) && is_array($_plugins[$location]) ? count($_plugins[$location]) : 0;
}

/**
 * including the necessary plugins
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function api_plugin($location) {
	global $_plugins;
	if (isset($_plugins[$location]) && is_array($_plugins[$location])) {
		foreach ($_plugins[$location] as $this_plugin) {
			include api_get_path(SYS_PLUGIN_PATH)."$this_plugin/index.php";
		}
	}
}

/**
 * Checks to see wether a certain plugin is installed.
 * @return boolean true if the plugin is installed, false otherwise.
 */
function api_is_plugin_installed($plugin_list, $plugin_name) {
	foreach ($plugin_list as $plugin_location) {
		if (array_search($plugin_name, $plugin_location) !== false) { return true; }
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
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
		return str_replace(array("[tex]", "[/tex]"), array("<object classid=\"clsid:5AFAB315-AD87-11D3-98BB-002035EFB1A4\"><param name=\"autosize\" value=\"true\" /><param name=\"DataType\" value=\"0\" /><param name=\"Data\" value=\"", "\" /></object>"), $textext);
	}
	return str_replace(array("[tex]", "[/tex]"), array("<embed type=\"application/x-techexplorer\" texdata=\"", "\" autosize=\"true\" pluginspage=\"http://www.integretechpub.com/techexplorer/\">"), $textext);
}

/**
 * Transforms a number of seconds in hh:mm:ss format
 * @author Julian Prud'homme
 * @param integer the number of seconds
 * @return string the formated time
 */
function api_time_to_hms($seconds) {

	//if seconds = -1, it means we have wrong datas in the db
	if ($seconds == -1) {
		return get_lang('Unknown').Display::return_icon('info2.gif', get_lang('WrongDatasForTimeSpentOnThePlatform'), array('align' => 'absmiddle', 'hspace' => '3px'));
	}

	//How many hours ?
	$hours = floor($seconds / 3600);

	//How many minutes ?
	$min = floor(($seconds - ($hours * 3600)) / 60);

	//How many seconds
	$sec = floor($seconds - ($hours * 3600) - ($min * 60));

	if ($sec < 10) {
		$sec = "0$sec";
	}

	if ($min < 10) {
		$min = "0$min";
	}

	return "$hours:$min:$sec";
}

// TODO: This function is to be simplified. File access modes to be implemented.
/**
 * function adapted from a php.net comment
 * copy recursively a folder
 * @param the source folder
 * @param the dest folder
 * @param an array of excluded file_name (without extension)
 * @param copied_files the returned array of copied files
 */

function copyr($source, $dest, $exclude = array(), $copied_files = array()) {
	// Simple copy for a file
	if (is_file($source)) {
		$path_info = pathinfo($source);
		if (!in_array($path_info['filename'], $exclude)) {
			copy($source, $dest);
		}
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
			$files = copyr("$source/$entry", "$dest/$entry", $exclude, $copied_files);
		}
	}
	// Clean up
	$dir->close();
	return $files;
}

/**
 * Deletes a file, or a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.3
 * @param       string   $dirname    Directory to delete
 * @return      bool     Returns TRUE on success, FALSE on failure
 * @link http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/
 * @author		Yannick Warnier, adaptation for the Dokeos LMS, April, 2008
 * @author		Ivan Tcholakov, a sanity check about Directory class creation has been added, September, 2009
 */
function rmdirr($dirname) {
	// A sanity check
	if (!file_exists($dirname)) {
		return false;
	}

	// Simple delete for a file
	if (is_file($dirname) || is_link($dirname)) {
		$res = unlink($dirname);
		if ($res === false) {
			error_log(__FILE__.' line '.__LINE__.': '.((bool)ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
		}
		return $res;
	}

	// Loop through the folder
	$dir = dir($dirname);
	// A sanity check
	$is_object_dir = is_object($dir);
	if ($is_object_dir) {
		while (false !== $entry = $dir->read()) {
			// Skip pointers
			if ($entry == '.' || $entry == '..') {
				continue;
			}

			// Recurse
			rmdirr("$dirname/$entry");
		}
	}

	// Clean up
	if ($is_object_dir) {
		$dir->close();
	}
	$res = rmdir($dirname);
	if ($res === false) {
		error_log(__FILE__.' line '.__LINE__.': '.((bool)ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
	}
	return $res;
}

function copy_folder_course_session($pathname, $base_path_document,$session_id,$course_info, $document)
{
	$table = Database :: get_course_table(TABLE_DOCUMENT, $course_info['dbName']);
	$session_id = intval($session_id);
    // Check if directory already exists
    if (is_dir($pathname) || empty($pathname)) {
        return true;
    }

    // Ensure a file does not already exist with the same name
    if (is_file($pathname)) {
        trigger_error('mkdirr() File exists', E_USER_WARNING);
        return false;
    }

    $folders = explode(DIRECTORY_SEPARATOR,str_replace($base_path_document.DIRECTORY_SEPARATOR,'',$pathname));

    $new_pathname = $base_path_document;
    $path = '';

    foreach ($folders as $folder) {
	$new_pathname .= DIRECTORY_SEPARATOR.$folder;
	$path .= DIRECTORY_SEPARATOR.$folder;

	if (!file_exists($new_pathname)) {

		$sql = "SELECT * FROM $table WHERE path = '$path' AND filetype = 'folder' AND session_id = '$session_id'";
		$rs1  = Database::query($sql,__FILE__,__LINE__);
		$num_rows = Database::num_rows($rs1);

		if ($num_rows == 0) {
			if (mkdir($new_pathname)) {
				$perm = api_get_setting('permissions_for_new_directories');
				$perm = octdec(!empty($perm)?$perm:'0770');
				chmod($new_pathname,$perm);
			}
			// Insert new folder with destination session_id
			$sql = "INSERT INTO ".$table." SET path = '$path', comment = '".Database::escape_string($document->comment)."', title = '".Database::escape_string(basename($new_pathname))."' ,filetype='folder', size= '0', session_id = '$session_id'";
			Database::query($sql, __FILE__, __LINE__);
			$document_id = Database::insert_id();
			api_item_property_update($course_info,TOOL_DOCUMENT,$document_id,'FolderCreated',api_get_user_id(),0,0,null,null,$session_id);
		}
	}

    } // en foreach

}

// TODO: chmodr() is a better name. Some corrections are needed.
function api_chmod_R($path, $filemode) {
	if (!is_dir($path)) {
		return chmod($path, $filemode);
	}

	$handler = opendir($path);
	while ($file = readdir($handler)) {
		if ($file != '.' && $file != '..') {
			$fullpath = "$path/$file";
			if (!is_dir($fullpath)) {
				if (!chmod($fullpath, $filemode)) {
					return false;
				}
			} else {
				if (!api_chmod_R($fullpath, $filemode)) {
					return false;
				}
			}
		}
	}

	closedir($handler);
	return chmod($path, $filemode);
}

/**
 * Get Dokeos version from the configuration files
 * @return	string	A string of type "1.8.4", or an empty string if the version could not be found
 */
function api_get_version() {
	global $_configuration;
	return (string)$_configuration['dokeos_version'];
}

/**
 * Check if status given in parameter exists in the platform
 * @param mixed the status (can be either int either string)
 * @return true if the status exists, else returns false
 */
function api_status_exists($status_asked) {
	global $_status_list;
	return in_array($status_asked, $_status_list) ? true : isset($_status_list[$status_asked]);
}

/**
 * Check if status given in parameter exists in the platform
 * @param mixed the status (can be either int either string)
 * @return true if the status exists, else returns false
 */
function api_status_key($status) {
	global $_status_list;
	return isset($_status_list[$status]) ? $status : array_search($status, $_status_list);
}

/**
 * get the status langvars list
 * @return array the list of status with their translations
 */
function api_get_status_langvars() {
	return array(
		COURSEMANAGER => get_lang('Teacher', ''),
		SESSIONADMIN => get_lang('SessionsAdmin', ''),
		DRH => get_lang('Drh', ''),
		STUDENT => get_lang('Student', ''),
		ANONYMOUS => get_lang('Anonymous', '')
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
function api_set_setting($var, $value, $subvar = null, $cat = null, $access_url = 1) {
	if (empty($var)) { return false; }
	$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$var = Database::escape_string($var);
	$value = Database::escape_string($value);
	$access_url = (int)$access_url;
	if (empty($access_url)) { $access_url = 1; }
	$select = "SELECT id FROM $t_settings WHERE variable = '$var' ";
	if (!empty($subvar)) {
		$subvar = Database::escape_string($subvar);
		$select .= " AND subkey = '$subvar'";
	}
	if (!empty($cat)) {
		$cat = Database::escape_string($cat);
		$select .= " AND category = '$cat'";
	}
	if ($access_url > 1) {
		$select .= " AND access_url = $access_url";
	} else {
		$select .= " AND access_url = 1 ";
	}

	$res = Database::query($select, __FILE__, __LINE__);
	if (Database::num_rows($res) > 0) {
		//found item for this access_url
		$row = Database::fetch_array($res);
		$update = "UPDATE $t_settings SET selected_value = '$value' WHERE id = ".$row['id'] ;
		$res = Database::query($update, __FILE__, __LINE__);
	} else {
		//Item not found for this access_url, we have to check if it exist with access_url = 1
		$select = "SELECT * FROM $t_settings WHERE variable = '$var' AND access_url = 1 ";
		// just in case
		if ($access_url == 1) {
			if (!empty($subvar)) {
				$select .= " AND subkey = '$subvar'";
			}
			if (!empty($cat)) {
				$select .= " AND category = '$cat'";
			}
			$res = Database::query($select, __FILE__, __LINE__);

			if (Database::num_rows($res) > 0) { //we have a setting for access_url 1, but none for the current one, so create one
				$row = Database::fetch_array($res);
				$insert = "INSERT INTO $t_settings " .
						"(variable,subkey," .
						"type,category," .
						"selected_value,title," .
						"comment,scope," .
						"subkeytext,access_url)" .
						" VALUES " .
						"('".$row['variable']."',".(!empty($row['subkey']) ? "'".$row['subkey']."'" : "NULL")."," .
						"'".$row['type']."','".$row['category']."'," .
						"'$value','".$row['title']."'," .
						"".(!empty($row['comment']) ? "'".$row['comment']."'" : "NULL").",".(!empty($row['scope']) ? "'".$row['scope']."'" : "NULL")."," .
						"".(!empty($row['subkeytext'])?"'".$row['subkeytext']."'":"NULL").",$access_url)";
				$res = Database::query($insert, __FILE__, __LINE__);
			} else { // this setting does not exist
				error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all', 0);
			}
		} else {
			// other access url
			if (!empty($subvar)) {
				$select .= " AND subkey = '$subvar'";
			}
			if (!empty($cat)) {
				$select .= " AND category = '$cat'";
			}
			$res = Database::query($select, __FILE__, __LINE__);

			if (Database::num_rows($res) > 0) { //we have a setting for access_url 1, but none for the current one, so create one
				$row = Database::fetch_array($res);
				if ($row['access_url_changeable'] == 1) {
					$insert = "INSERT INTO $t_settings " .
							"(variable,subkey," .
							"type,category," .
							"selected_value,title," .
							"comment,scope," .
							"subkeytext,access_url, access_url_changeable)" .
							" VALUES " .
							"('".$row['variable']."',".
							(!empty($row['subkey']) ? "'".$row['subkey']."'" : "NULL")."," .
							"'".$row['type']."','".$row['category']."'," .
							"'$value','".$row['title']."'," .
							"".(!empty($row['comment']) ? "'".$row['comment']."'" : "NULL").",".
							(!empty($row['scope']) ? "'".$row['scope']."'" : "NULL")."," .
							"".(!empty($row['subkeytext']) ? "'".$row['subkeytext']."'" : "NULL").",$access_url,".$row['access_url_changeable'].")";
					$res = Database::query($insert, __FILE__, __LINE__);
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
function api_set_settings_category($category, $value = null, $access_url = 1) {
	if (empty($category)) { return false; }
	$category = Database::escape_string($category);
	$t_s = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$access_url = (int) $access_url;
	if (empty($access_url)) { $access_url = 1; }
	if (isset($value)) {
		$value = Database::escape_string($value);
		$sql = "UPDATE $t_s SET selected_value = '$value' WHERE category = '$category' AND access_url = $access_url";
		$res = Database::query($sql, __FILE__, __LINE__);
		return $res !== false;
	}
	$sql = "UPDATE $t_s SET selected_value = NULL WHERE category = '$category' AND access_url = $access_url";
	$res = Database::query($sql, __FILE__, __LINE__);
	return $res !== false;
}

/**
 * Get all available access urls in an array (as in the database)
 * @return	array	Array of database records
 */
function api_get_access_urls($from = 0, $to = 1000000, $order = 'url', $direction = 'ASC') {
	$t_au = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
	$from = (int) $from;
	$to = (int) $to;
	$order = Database::escape_string($order);
	$direction = Database::escape_string($direction);
	$sql = "SELECT id, url, description, active, created_by, tms FROM $t_au ORDER BY $order $direction LIMIT $to OFFSET $from";
	$res = Database::query($sql, __FILE__, __LINE__);
	return Database::store_result($res);
}

/**
 * Get the access url info in an array
 * @param 	id of the access url
 * @return	array Array with all the info (url, description, active, created_by, tms) from the access_url table
 * @author 	Julio Montoya Armas
 */
function api_get_access_url($id) {
	global $_configuration;
	$id = Database::escape_string(intval($id));
	$result = array(); // Is this line necessary?
	// calling the Database:: library dont work this is handmade
	//$table_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
	$table = 'access_url';
	$database = $_configuration['main_database'];
	$table_access_url =  "`".$database."`.`".$table."`";
	$sql = "SELECT url, description, active, created_by, tms
			FROM $table_access_url WHERE id = '$id' ";
	$res = Database::query($sql, __FILE__, __LINE__);
	$result = @Database::fetch_array($res);
	return $result;
}

/**
 * Adds an access URL into the database
 * @param	string	URL
 * @param	string	Description
 * @param	int		Active (1= active, 0=disabled)
 * @return	int		The new database id, or the existing database id if this url already exists
 */
function api_add_access_url($u, $d = '', $a = 1) {
	$t_au = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
	$u = Database::escape_string($u);
	$d = Database::escape_string($d);
	$a = (int) $a;
	$sql = "SELECT id FROM $t_au WHERE url LIKE '$u'";
	$res = Database::query($sql, __FILE__, __LINE__);
	if ($res === false) {
		//problem querying the database - return false
		return false;
	}
	if (Database::num_rows($res) > 0) {
		return Database::result($res, 0, 'id');
	}
	$ui = api_get_user_id();
	/*
	$time =
	*/
	$sql = "INSERT INTO $t_au (url,description,active,created_by,tms) VALUES ('$u','$d',$a,$ui,'')";
	$res = Database::query($sql, __FILE__, __LINE__);
	return ($res === false) ? false : Database::insert_id();
}

/**
 * Gets all the current settings for a specific access url
 * @param	string	The category, if any, that we want to get
 * @param	string	Whether we want a simple list (display a catgeory) or a grouped list (group by variable as in settings.php default). Values: 'list' or 'group'
 * @param	int		Access URL's ID. Optional. Uses 1 by default, which is the unique URL
 * @return	array	Array of database results for the current settings of the current access URL
 */
function api_get_settings($cat = null, $ordering = 'list', $access_url = 1, $url_changeable = 0) {
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$access_url = (int) $access_url;

	$url_changeable_where = '';
	if ($url_changeable == 1) {
		$url_changeable_where= " AND access_url_changeable= '1' ";
	}
	if (empty($access_url)) { $access_url = 1; }
	$sql = "SELECT id, variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable " .
			" FROM $t_cs WHERE access_url = $access_url  $url_changeable_where ";
	if (!empty($cat)) {
		$cat = Database::escape_string($cat);
		$sql .= " AND category='$cat' ";
	}
	if ($ordering == 'group') {
		$sql .= " GROUP BY variable ORDER BY id ASC";
	} else {
		$sql .= " ORDER BY 1,2 ASC";
	}
	$res = Database::query($sql, __FILE__, __LINE__);
	return Database::store_result($res);
}

/**
 * Gets the distinct settings categories
 * @param	array	Array of strings giving the categories we want to exclude
 * @param	int		Access URL. Optional. Defaults to 1
 * @return	array	A list of categories
 */
function api_get_settings_categories($exceptions = array(), $access_url = 1) {
	$access_url = (int) $access_url;
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$list = "'".implode("','",$exceptions)."'";
	$sql = "SELECT DISTINCT category FROM $t_cs";
	if ($list != "'',''" and $list != "''" and !empty($list)) {
		$sql .= " WHERE category NOT IN ($list)";
	}
	$r = Database::query($sql, __FILE__, __LINE__);
	return Database::store_result($r);
}

/**
 * Delete setting
 * @param	string	Variable
 * @param	string	Subkey
 * @param	int		Access URL
 * @return	boolean	False on failure, true on success
 */
function api_delete_setting($v, $s = null, $a = 1) {
	if (empty($v)) { return false; }
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$v = Database::escape_string($v);
	$a = (int) $a;
	if (empty($a)) { $a = 1; }
	if (!empty($s)) {
		$s = Database::escape_string($s);
		$sql = "DELETE FROM $t_cs WHERE variable = '$v' AND subkey = '$s' AND access_url = $a";
		$r = Database::query($sql, __FILE__, __LINE__);
		return $r;
	}
	$sql = "DELETE FROM $t_cs WHERE variable = '$v' AND access_url = $a";
	$r = Database::query($sql, __FILE__, __LINE__);
	return $r;
}

/**
 * Delete all the settings from one category
 * @param	string	Category
 * @param	int		Access URL
 * @return	boolean	False on failure, true on success
 */
function api_delete_category_settings($c, $a = 1) {
	if (empty($c)) { return false; }
	$t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$c = Database::escape_string($c);
	$a = (int) $a;
	if (empty($a)) { $a = 1; }
	$sql = "DELETE FROM $t_cs WHERE category = '$c' AND access_url = $a";
	$r = Database::query($sql, __FILE__, __LINE__);
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
function api_add_setting($val, $var, $sk = null, $type = 'textfield', $c = null, $title = '', $com = '', $sc = null, $skt = null, $a = 1, $v = 0) {
	if (empty($var) || !isset($val)) { return false; }
	$t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
	$var = Database::escape_string($var);
	$val = Database::escape_string($val);
	$a = (int) $a;
	if (empty($a)) { $a = 1; }
	//check if this variable doesn't exist already
	$select = "SELECT id FROM $t_settings WHERE variable = '$var' ";
	if (!empty($sk)) {
		$sk = Database::escape_string($sk);
		$select .= " AND subkey = '$sk'";
	}
	if ($a > 1) {
		$select .= " AND access_url = $a";
	} else {
		$select .= " AND access_url = 1 ";
	}
	$res = Database::query($select, __FILE__, __LINE__);
	if (Database::num_rows($res) > 0) { //found item for this access_url
		$row = Database::fetch_array($res);
		return $row['id'];
	}

	//item not found for this access_url, we have to check if the whole thing is missing
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
	if (isset($c)) {//category
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
	if (isset($title)) {//title
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
	if (isset($sc)) {//scope
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
	$res = Database::query($insert, __FILE__, __LINE__);
	return $res;
}

/**
 * Returns wether a user can or can't view the contents of a course.
 *
 * @param   int $userid     User id or NULL to get it from $_SESSION
 * @param   int $cid        Course id to check whether the user is allowed.
 * @return  bool
 */
function api_is_course_visible_for_user($userid = null, $cid = null) {
	if ($userid == null) {
		$userid = $_SESSION['_user']['user_id'];
	}
	if (empty ($userid) || strval(intval($userid)) != $userid) {
		if (api_is_anonymous()) {
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

	$result = Database::query($sql, __FILE__, __LINE__);

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

		$result = Database::query($sql, __FILE__, __LINE__);

		if (Database::num_rows($result) > 0) {
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

		$result = Database::query($sql, __FILE__, __LINE__);

		if (Database::num_rows($result) > 0) {
			// this  user have a recorded state for this course
			$cuData = Database::fetch_array($result);

			$_courseUser['role'] = $cuData['role'];
			$is_courseMember     = true;
			$is_courseTutor      = ($cuData['tutor_id' ] == 1);
			$is_courseAdmin      = ($cuData['status'] == 1);
		}
		if (!$is_courseAdmin) {
			// this user has no status related to this course
			// is it the session coach or the session admin ?
			$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
			$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

			$sql = "SELECT
						session.id_coach, session_admin_id, session.id
					FROM
						$tbl_session as session
					INNER JOIN $tbl_session_course
						ON session_rel_course.id_session = session.id
						AND session_rel_course.course_code = '$cid'
					LIMIT 1";

			$result = Database::query($sql, __FILE__, __LINE__);
			$row = Database::store_result($result);

			if ($row[0]['id_coach'] == $userid) {
				$_courseUser['role'] = 'Professor';
				$is_courseMember = true;
				$is_courseTutor = true;
				$is_courseAdmin = false;
				$is_courseCoach = true;
				$is_sessionAdmin = false;
				api_session_register('_courseUser');
			}
			elseif ($row[0]['session_admin_id'] == $userid) {
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

				$result = Database::query($sql, __FILE__, __LINE__);

				if ($row = Database::fetch_array($result)) {
					$_courseUser['role'] = 'Professor';
					$is_courseMember = true;
					$is_courseTutor = true;
					$is_courseCoach = true;
					$is_sessionAdmin = false;

					$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

					$sql = "SELECT status FROM $tbl_user
							WHERE  user_id = $userid  LIMIT 1";

					$result = Database::query($sql, __FILE__, __LINE__);

					if (Database::result($result, 0, 0) == 1) {
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

					if (Database::num_rows($result) > 0) {
						// this  user have a recorded state for this course
						while ($row = Database::fetch_array($result)) {
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

	switch ($visibility) {
		case COURSE_VISIBILITY_OPEN_WORLD:
			return true;
		case COURSE_VISIBILITY_OPEN_PLATFORM:
			return isset($userid);
		case COURSE_VISIBILITY_REGISTERED:
		case COURSE_VISIBILITY_CLOSED:
			return $is_platformAdmin || $is_courseMember || $is_courseAdmin;
	}
	return false;
}

/**
 * Returns whether an element (forum, message, survey ...) belongs to a session or not
 * @param String the tool of the element
 * @param int the element id in database
 * @param int the session_id to compare with element session id
 * @return boolean true if the element is in the session, false else
 */
function api_is_element_in_the_session($tool, $element_id, $session_id = null) {
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
	$rs = Database::query($sql, __FILE__, __LINE__);
	if ($element_session_id = Database::result($rs, 0, 0)) {
		if ($element_session_id == intval($session_id)) { // element belongs to the session
			return true;
		}
	}
	return false;
}

// TODO: The PHP team considers ereg* functions as deprecated. Functions from PCRE should be used.
/**
 * Replaces "forbidden" characters in a filename string.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author René Haentjens, UGent (RH)
 * @author Ivan Tcholakov, JUN-2009.		Transliteration functionality has been added.
 * @param  string $filename					The filename string.
 * @param  string $strict (optional)		When it is 'strict', all non-ASCII charaters will be replaced. Additional ASCII replacemets will be done too.
 * @return string							The cleaned filename.
 */

function replace_dangerous_char($filename, $strict = 'loose') {
	static $search  = array(' ', '/', '\\', '"', '\'', '?', '*', '>', '<', '|', ':', '$', '(', ')', '^', '[', ']', '#');
	static $replace = array('_', '-', '-',  '-', '_',  '-', '-', '',  '-', '-', '-', '-', '-', '-', '-', '-', '-', '-');
	static $search_strict  = array('-');
	static $replace_strict = array('_');

	$system_encoding = api_get_file_system_encoding();

	// Compatibility: we keep the previous behaviour (Dokeos 1.8.6) for Latin 1 platforms (ISO-8859-15, ISO-8859-1, WINDOWS-1252, ...).
	if (api_is_latin1($system_encoding)) {
		$filename = ereg_replace("\.+$", "", substr(strtr(ereg_replace(
			"[^!-~\x80-\xFF]", "_", trim($filename)), '\/:*?"<>|\'',
			/* Keep C1 controls for UTF-8 streams */  '-----_---_'), 0, 250));
		if ($strict != 'strict') return $filename;
		return ereg_replace("[^!-~]", 'x', $filename);
	}

	// For other platform encodings and various languages we use transliteration to ASCII filename string.
	if (!api_is_valid_utf8($filename)) {
		// Here we need to convert the file name to UTF-8 string first. We will try to guess the input encoding.
		$input_encoding = api_get_file_system_encoding();
		if (api_is_utf8($input_encoding)) {
			$input_encoding = $system_encoding;
		}
		if (api_is_utf8($input_encoding)) {
			$input_encoding = api_get_non_utf8_encoding(api_get_interface_language()); // This is a "desperate" try.
		}
		$filename = api_utf8_encode($filename, $input_encoding);
	}
	// Transliteration.
	$filename = api_transliterate($filename, 'x', 'UTF-8');
	$filename = trim($filename);
	// Trimming any leading/trailing dots.
	$filename = trim($filename, '.');
	$filename = trim($filename);
	// Replacing other remaining dangerous characters.
	$filename = str_replace($search, $replace, $filename);
	if ($strict == 'strict') {
		$filename = str_replace($search_strict, $replace_strict, $filename);
		$filename = preg_replace('/[^0-9A-Za-z_.-]/', '', $filename);
	}
	// Length is limited, so the file name to be acceptable by some operating systems.
	return substr($filename, 0, 250);
}

/**
 * Fixes the $_SERVER["REQUEST_URI"] that is empty in IIS6.
 * @author Ivan Tcholakov, 28-JUN-2006.
 */
function api_request_uri() {
	if (!empty($_SERVER['REQUEST_URI'])) {
		return $_SERVER['REQUEST_URI'];
	}
	$uri = $_SERVER['SCRIPT_NAME'];
	if (!empty($_SERVER['QUERY_STRING'])) {
		$uri .= '?'.$_SERVER['QUERY_STRING'];
	}
	$_SERVER['REQUEST_URI'] = $uri;
	return $uri;
}

/**
 * Creates the "include_path" php-setting, following the rule that
 * PEAR packages of Dokeos should be read before other external packages.
 * To be used in global.inc.php only.
 * @author Ivan Tcholakov, 06-NOV-2008.
 */
function api_create_include_path_setting() {
	$include_path = ini_get('include_path');
	if (!empty($include_path)) {
		$include_path_array = explode(PATH_SEPARATOR, $include_path);
		$dot_found = array_search('.', $include_path_array);
		if ($dot_found !== false) {
			$result = array();
			foreach ($include_path_array as $path) {
				$result[] = $path;
				if ($path == '.') {
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
function api_get_current_access_url_id() {
	$access_url_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
	$path = api_get_path(WEB_PATH);
	$sql = "SELECT id FROM $access_url_table WHERE url = '".$path."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if (Database::num_rows($result) > 0) {
		$access_url_id = Database::result($result, 0, 0);
		return $access_url_id;
	}
	return -1;
}

/** Gets the registered urls from a given user id
 * @author Julio Montoya <gugli100@gmail.com>
 * @return int user id
 */
function api_get_access_url_from_user($user_id) {
	$user_id = intval($user_id);
	$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
	$table_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
	$sql = "SELECT access_url_id FROM $table_url_rel_user url_rel_user INNER JOIN $table_url u
			ON (url_rel_user.access_url_id = u.id)
			WHERE user_id = ".Database::escape_string($user_id);
	$result = Database::query($sql, __FILE__, __LINE__);
	$url_list = array();
	while ($row = Database::fetch_array($result, 'ASSOC')) {
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
function api_get_status_of_user_in_course ($user_id, $course_code) {
	$tbl_rel_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$user_id 	 = Database::escape_string(intval($user_id));
	$course_code = Database::escape_string($course_code);
	$sql = 'SELECT status FROM '.$tbl_rel_course_user.'
		WHERE user_id='.$user_id.' AND course_code="'.$course_code.'";';
	$result = Database::query($sql, __FILE__, __LINE__);
	$row_status = Database::fetch_array($result, 'ASSOC');
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
			return $course_code == $_SESSION['_course']['sysCode'];
		}
		return true;
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
			return $group_id == $_SESSION['_gid'];
		} else {
			return true;
		}
	}
	return false;
}

// sys_get_temp_dir() is on php since 5.2.1
if (!function_exists('sys_get_temp_dir')) {

	// Based on http://www.phpit.net/
	// article/creating-zip-tar-archives-dynamically-php/2/
	function sys_get_temp_dir() {

		// Try to get from environment variable
		if (!empty($_ENV['TMP'])) {
			return realpath($_ENV['TMP']);
		}
		if (!empty($_ENV['TMPDIR'])) {
			return realpath($_ENV['TMPDIR']);
		}
		if (!empty($_ENV['TEMP'])) {
			return realpath($_ENV['TEMP']);
		}

		// Detect by creating a temporary file
		// Try to use system's temporary directory
		// as random name shouldn't exist
		$temp_file = tempnam(md5(uniqid(rand(), true)), '');
		if ($temp_file) {
			$temp_dir = realpath(dirname($temp_file));
			@unlink( $temp_file );
			return $temp_dir;
		}

		return false;
	}
}

/**
 * This function informs whether the sent request is XMLHttpRequest
 */
function api_is_xml_http_request() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// TODO: To be moved to UserManager class.
/**
 * This function gets the hash in md5 or sha1 (it depends in the platform config) of a given password
 * @param  string password
 * @return string password with the applied hash
 */
function api_get_encrypted_password($password, $salt = '') {

	global $userPasswordCrypted;
	switch ($userPasswordCrypted){
		case 'md5':
			return empty($salt) ? md5($password) : md5($password.$salt);
		case 'sha1':
			return empty($salt) ? sha1($password) : sha1($password.$salt);
		case 'none':
			return $password;
		default:
			return empty($salt) ? md5($password) : md5($password.$salt);
	}
}

/** Check if a secret key is valid
 *  @param string $original_key_secret  - secret key from (webservice) client
 *  @param string $security_key - security key from dokeos
 *  @return boolean - true if secret key is valid, false otherwise
 */
function api_is_valid_secret_key($original_key_secret, $security_key) {
	global $_configuration;
	return $original_key_secret == sha1($security_key);
}

/**
 * Check if a user is into course
 * @param string $course_id - the course id
 * @param string $user_id - the user id
 */
function api_is_user_of_course($course_id, $user_id) {
	$tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$sql = 'SELECT user_id FROM '.$tbl_course_rel_user.' WHERE course_code="'.Database::escape_string($course_id).'" AND user_id="'.Database::escape_string($user_id).'"';
	$result = Database::query($sql, __FILE__, __LINE__);
	return Database::num_rows($result) == 1;
}

/**
 * Checks whether the server's operating system is Windows (TM).
 * @return boolean - true if the operating system is Windows, false otherwise
 */
function api_is_windows_os() {
	if (function_exists("php_uname")) {
		// php_uname() exists as of PHP 4.0.2, according to the documentation.
		// We expect that this function will always work for Dokeos 1.8.x.
		$os = php_uname();
	}
	// The following methods are not needed, but let them stay, just in case.
	elseif (isset($_ENV['OS'])) {
		// Sometimes $_ENV['OS'] may not be present (bugs?)
		$os = $_ENV['OS'];
	}
	elseif (defined('PHP_OS')) {
		// PHP_OS means on which OS PHP was compiled, this is why
		// using PHP_OS is the last choice for detection.
		$os = PHP_OS;
	} else {
		return false;
	}
	return strtolower(substr((string)$os, 0, 3 )) == 'win';
}

/**
 * This wrapper function has been implemented for avoiding some known problems about the function getimagesize().
 * @link http://php.net/manual/en/function.getimagesize.php
 * @link http://www.dokeos.com/forum/viewtopic.php?t=12345
 * @link http://www.dokeos.com/forum/viewtopic.php?t=16355
 */
function api_getimagesize($path) {
	return @getimagesize(preg_match(VALID_WEB_PATH, $path) ? (api_is_internal_path($path) ? api_get_path(TO_SYS, $path) : $path) : $path);
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
	$image_properties = api_getimagesize($image);
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

/**
 * return list of tools
 * @author Isaac flores paz <florespaz@bidsoftperu.com>
 * @param string The tool name to filter
 * @return mixed Filtered string or array
 */
function api_get_tools_lists($my_tool = null) {
	$tools_list = array(
		TOOL_DOCUMENT,TOOL_THUMBNAIL,TOOL_HOTPOTATOES,
		TOOL_CALENDAR_EVENT,TOOL_LINK,TOOL_COURSE_DESCRIPTION,TOOL_SEARCH,
		TOOL_LEARNPATH,TOOL_ANNOUNCEMENT,TOOL_FORUM,TOOL_THREAD,TOOL_POST,
		TOOL_DROPBOX,TOOL_QUIZ,TOOL_USER,TOOL_GROUP,TOOL_BLOGS,TOOL_CHAT,
		TOOL_CONFERENCE,TOOL_STUDENTPUBLICATION,TOOL_TRACKING,TOOL_HOMEPAGE_LINK,
		TOOL_COURSE_SETTING,TOOL_BACKUP,TOOL_COPY_COURSE_CONTENT,TOOL_RECYCLE_COURSE,
		TOOL_COURSE_HOMEPAGE,TOOL_COURSE_RIGHTS_OVERVIEW,TOOL_UPLOAD,TOOL_COURSE_MAINTENANCE,
		TOOL_VISIO,TOOL_VISIO_CONFERENCE,TOOL_VISIO_CLASSROOM,TOOL_SURVEY,TOOL_WIKI,
		TOOL_GLOSSARY,TOOL_GRADEBOOK,TOOL_NOTEBOOK
	);
	if (empty($my_tool)) {
		return $tools_list;
	}
	return in_array($my_tool, $tools_list) ? $my_tool : '';
}

/**
 * Checks if we already approved the last version term and condition
 * @param int user id
 * @return bool true if we pass false otherwise
 */
function api_check_term_condition($user_id) {
	if (api_get_setting('allow_terms_conditions') == 'true') {
		require_once api_get_path(LIBRARY_PATH).'legal.lib.php';
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		//check if exists terms and conditions
		if (LegalManager::count() == 0) {
			return true;
		}
		//check the last user version_id passed
		$sqlv = "SELECT field_value FROM $t_ufv ufv inner join $t_uf uf on ufv.field_id= uf.id
				WHERE field_variable = 'legal_accept' AND user_id = ".intval($user_id);
		$resv = Database::query($sqlv, __FILE__, __LINE__);
		if (Database::num_rows($resv) > 0) {
			$rowv = Database::fetch_row($resv);
			$rowv = $rowv[0];
			$user_conditions = explode(':', $rowv);
			$version = $user_conditions[0];
			$lang_id = $user_conditions[1];
			$real_version = LegalManager::get_last_version($lang_id);
			return $version >= $real_version;
		}
		return false;
	}
	return false;
}

/**
 * Get all information of the tool into course
 * @param int The tool id
 * @return array
 */
function api_get_tool_information($tool_id) {
	$t_tool = Database::get_course_table(TABLE_TOOL_LIST);
	$sql = 'SELECT * FROM '.$t_tool.' WHERE id="'.Database::escape_string($tool_id).'"';
	$rs  = Database::query($sql, __FILE__, __LINE__);
	return Database::fetch_array($rs);
}


/*
==============================================================================
		DEPRECATED FUNCTIONS
==============================================================================
*/

/**
 * Deprecated, use api_trunc_str() instead.
 */
function shorten($input, $length = 15, $encoding = null) {
	$length = intval($length);
	if (!$length) {
		$length = 15;
	}
	return api_trunc_str($input, $length, '...', false, $encoding);
}

/**
 * DEPRECATED, use api_get_setting instead
 */
function get_setting($variable, $key = NULL) {
	global $_setting;
	return api_get_setting($variable, $key);
}

/**
 * @deprecated, use api_is_allowed_to_edit() instead
 */
function is_allowed_to_edit() {
	return api_is_allowed_to_edit();
}

/**
 * @deprecated 19-SEP-2009: Use api_get_path(TO_SYS, $url) instead.
 */
function api_url_to_local_path($url) {
	return api_get_path(TO_SYS, $url);
}

/**
 * @deprecated 27-SEP-2009: Use Database::store_result($result) instead.
 */
function api_store_result($result) {
	return Database::store_result($result);
}

/**
 * @deprecated 28-SEP-2009: Use Database::query($query, $file, $line) instead.
 */
function api_sql_query($query, $file = '', $line = 0) {
	return Database::query($query, $file, $line);
}
