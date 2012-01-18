<?php
/* For licensing terms, see /license.txt */

/**
 * This is a code library for Chamilo.
 * It is included by default in every Chamilo file (through including the global.inc.php)
 *
 * @package chamilo.library
 */


/**
 * Constants declaration
 */

// USER STATUS CONSTANTS
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
/** global status of a user: low security, necessary for inserting data from
 * the teacher through HTMLPurifier */
define('COURSEMANAGERLOWSECURITY', 10);



// Table of status
$_status_list[COURSEMANAGER] = 'teacher'; // 1
$_status_list[SESSIONADMIN] = 'session_admin'; // 3
$_status_list[DRH] = 'drh'; // 4
$_status_list[STUDENT] = 'user'; // 5
$_status_list[ANONYMOUS] = 'anonymous'; // 6


// COURSE VISIBILITY CONSTANTS
/** only visible for course admin */
define('COURSE_VISIBILITY_CLOSED', 0);
/** only visible for users registered in the course*/
define('COURSE_VISIBILITY_REGISTERED', 1);
/** Open for all registered users on the platform */
define('COURSE_VISIBILITY_OPEN_PLATFORM', 2);
/** Open for the whole world */
define('COURSE_VISIBILITY_OPEN_WORLD', 3);


// SESSION VISIBILITY CONSTANTS
define('SESSION_VISIBLE_READ_ONLY', 1);
define('SESSION_VISIBLE', 2);
define('SESSION_INVISIBLE', 3);

define('SUBSCRIBE_ALLOWED', 1);
define('SUBSCRIBE_NOT_ALLOWED', 0);
define('UNSUBSCRIBE_ALLOWED', 1);
define('UNSUBSCRIBE_NOT_ALLOWED', 0);

// CONSTANTS defining all tools, using the english version
/* When you add a new tool you must add it into function api_get_tools_lists() too */
define('TOOL_DOCUMENT','document');
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
define('TOOL_ATTENDANCE','attendance');
define('TOOL_COURSE_PROGRESS','course_progress');

// CONSTANTS defining Chamilo interface sections
define('SECTION_CAMPUS', 'mycampus');
define('SECTION_COURSES', 'mycourses');
define('SECTION_MYPROFILE', 'myprofile');
define('SECTION_MYAGENDA', 'myagenda');
define('SECTION_COURSE_ADMIN', 'course_admin');
define('SECTION_PLATFORM_ADMIN', 'platform_admin');
define('SECTION_MYGRADEBOOK', 'mygradebook');
define('SECTION_TRACKING','session_my_space');
define('SECTION_SOCIAL', 'social');
define('SECTION_DASHBOARD', 'dashboard');
define('SECTION_REPORTS', 'reports');
define('SECTION_GLOBAL', 'global');

// CONSTANT name for local authentication source
define('PLATFORM_AUTH_SOURCE', 'platform');

// CONSTANT defining the default HotPotatoes files directory
define('DIR_HOTPOTATOES','/HotPotatoes_files');

// event logs types
define('LOG_COURSE_DELETE', 				'course_deleted');
define('LOG_COURSE_CREATE', 				'course_created');
define('LOG_USER_DELETE', 					'user_deleted');
define('LOG_USER_CREATE', 					'user_created');
define('LOG_SESSION_CREATE', 				'session_created');
define('LOG_SESSION_DELETE', 				'session_deleted');
define('LOG_SESSION_CATEGORY_CREATE', 		'session_category_created');
define('LOG_SESSION_CATEGORY_DELETE', 		'session_category_deleted');
define('LOG_CONFIGURATION_SETTINGS_CHANGE', 'settings_changed');
define('LOG_SUBSCRIBE_USER_TO_COURSE', 		'user_subscribed');
define('LOG_UNSUBSCRIBE_USER_FROM_COURSE', 	'user_unsubscribed');

define('LOG_PROMOTION_CREATE', 	'promotion_created');
define('LOG_PROMOTION_DELETE', 	'promotion_deleted');
define('LOG_CAREER_CREATE', 	'career_created');
define('LOG_CAREER_DELETE', 	'career_deleted');

// event logs data types
define('LOG_COURSE_CODE',                       'course_code');
define('LOG_USER_ID',                           'user_id');
define('LOG_USER_OBJECT',                       'user_object');
define('LOG_SESSION_ID',                        'session_id');
define('LOG_SESSION_CATEGORY_ID',               'session_category_id');
define('LOG_CONFIGURATION_SETTINGS_CATEGORY',   'settings_category');
define('LOG_CONFIGURATION_SETTINGS_VARIABLE',   'settings_variable');
define('LOG_CAREER_ID',                         'career_id');
define('LOG_PROMOTION_ID',                      'promotion_id');

// Specification for usernames:
// 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 40 characters maximum length.
// 2. Empty username is formally valid, but it is reserved for the anonymous user.
define('USERNAME_MAX_LENGTH', 40);
define('USERNAME_PURIFIER', '/[^0-9A-Za-z_\.]/');
define('USERNAME_PURIFIER_SHALLOW', '/\s/');

// Constants for detection some important PHP5 subversions.
$php_version = (float) PHP_VERSION;

define('IS_PHP_52', !((float)$php_version < 5.2));
define('IS_PHP_53', !((float)$php_version < 5.3));

define('IS_PHP_SUP_OR_EQ_53', ($php_version >= 5.3));
define('IS_PHP_SUP_OR_EQ_52', ($php_version >= 5.2 && !IS_PHP_53));
define('IS_PHP_SUP_OR_EQ_51', ($php_version >= 5.1 && !IS_PHP_52 && !IS_PHP_53));



// This constant is a result of Windows OS detection, it has a boolean value:
// true whether the server runs on Windows OS, false otherwise.
define('IS_WINDOWS_OS', api_is_windows_os());

// Checks for installed optional php-extensions.
define('INTL_INSTALLED', function_exists('intl_get_error_code'));   // intl extension (from PECL), it is installed by default as of PHP 5.3.0
define('ICONV_INSTALLED', function_exists('iconv'));                // iconv extension, for PHP5 on Windows it is installed by default.
define('MBSTRING_INSTALLED', function_exists('mb_strlen'));         // mbstring extension.
define('DATE_TIME_INSTALLED', class_exists('DateTime'));            // datetime extension, it is moved to the core as of PHP 5.2, see http://www.php.net/datetime

// Patterns for processing paths.                                   // Examples:
define('REPEATED_SLASHES_PURIFIER', '/\/{2,}/');                    // $path = preg_replace(REPEATED_SLASHES_PURIFIER, '/', $path);
define('VALID_WEB_PATH', '/https?:\/\/[^\/]*(\/.*)?/i');            // $is_valid_path = preg_match(VALID_WEB_PATH, $path);
define('VALID_WEB_SERVER_BASE', '/https?:\/\/[^\/]*/i');            // $new_path = preg_replace(VALID_WEB_SERVER_BASE, $new_base, $path);

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
define('SYS_PLUGIN_PATH', 'SYS_PLUGIN_PATH');
define('PLUGIN_PATH', 'SYS_PLUGIN_PATH'); // deprecated ?
define('WEB_PLUGIN_PATH', 'WEB_PLUGIN_PATH');
define('SYS_ARCHIVE_PATH', 'SYS_ARCHIVE_PATH');
define('WEB_ARCHIVE_PATH', 'WEB_ARCHIVE_PATH');
define('INCLUDE_PATH', 'INCLUDE_PATH');
define('LIBRARY_PATH', 'LIBRARY_PATH');
define('CONFIGURATION_PATH', 'CONFIGURATION_PATH');
define('WEB_LIBRARY_PATH', 'WEB_LIBRARY_PATH');
define('WEB_AJAX_PATH', 'WEB_AJAX_PATH');
define('SYS_TEST_PATH', 'SYS_TEST_PATH');
define('WEB_TEMPLATE_PATH', 'WEB_TEMPLATE_PATH');
define('SYS_TEMPLATE_PATH', 'SYS_TEMPLATE_PATH');

// Constants for requesting path conversion.
define('TO_WEB', 'TO_WEB');
define('TO_SYS', 'TO_SYS');
define('TO_REL', 'TO_REL');

// Paths to regidtered specific resource files (scripts, players, etc.)
define('FLASH_PLAYER_AUDIO', '{FLASH_PLAYER_AUDIO}');
define('FLASH_PLAYER_VIDEO', '{FLASH_PLAYER_VIDEO}');
define('SCRIPT_SWFOBJECT', '{SCRIPT_SWFOBJECT}');
define('SCRIPT_ASCIIMATHML', '{SCRIPT_ASCIIMATHML}');
define('DRAWING_ASCIISVG', '{DRAWING_ASCIISVG}');

// Forcing PclZip library to use a custom temporary folder.
define('PCLZIP_TEMPORARY_DIR', api_get_path(SYS_ARCHIVE_PATH));

// Relations type with Human resources manager
define('COURSE_RELATION_TYPE_RRHH', 1);
define('SESSION_RELATION_TYPE_RRHH', 1);


/**
 * Inclusion of internationalization libraries
 */

require_once dirname(__FILE__).'/internationalization.lib.php';


/* PATHS & FILES - ROUTINES */

/**
 * Returns a path to a certain resource within the Chamilo area, specifyed through a parameter.
 * Also, this function provides conversion between path types, in this case the input path points inside the Chamilo area too.
 *
 * See $_configuration['course_folder'] in the configuration.php to alter the WEB_COURSE_PATH and SYS_COURSE_PATH parameters.
 * @param string $type              The requested path type (a defined constant), see the examples.
 * @param string $path (optional)   A path which type is to be converted. Also, it may be a defined constant for a path.
 * This parameter has meaning when $type parameter has one of the following values: TO_WEB, TO_SYS, TO_REL. Otherwise it is ignored.
 * @return string                   The requested path or the converted path.
 *
 * A terminology note:
 * The defined constants used by this function contain the abbreviations WEB, REL, SYS with the following meaning for types:
 * WEB - an absolute URL (we often call it web-path),
 * example: http://www.mychamilo.org/chamilo/courses/COURSE01/document/lesson01.html;
 *
 * REL - represents a semi-absolute URL - a web-path, which is relative to the root web-path of the server, without server's base,
 * example: /chamilo/courses/COURSE01/document/lesson01.html;
 *
 * SYS - represents an absolute path inside the scope of server's file system,
 * /var/www/chamilo/courses/COURSE01/document/lesson01.html or
 * C:/Inetpub/wwwroot/chamilo/courses/COURSE01/document/lesson01.html.
 *
 * In some abstract sense we can consider these three path types as absolute.
 *
 * Notes about the current behaviour model:
 * 1. Windows back-slashes are converted to slashes in the result.
 * 2. A semi-absolute web-path is detected by its leading slash. On Linux systems, absolute system paths start with
 * a slash too, so an additional check about presense of leading system server base is implemented. For example, the function is
 * able to distinguish type difference between /var/www/chamilo/courses/ (SYS) and /chamilo/courses/ (REL).
 * 3. The function api_get_path() returns only these three types of paths, which in some sense are absolute. The function has
 * no a mechanism for processing relative web/system paths, such as: lesson01.html, ./lesson01.html, ../css/my_styles.css.
 * It has not been identified as needed yet.
 * 4. Also, resolving the meta-symbols "." and ".." withiin paths has not been implemented, it is to be identified as needed.
 *
 * Example:
 * Assume that your server root is /var/www/ , Chamilo is installed in a subfolder chamilo/ and the URL of your campus is http://www.mychamilo.org
 * The other configuration paramaters have not been changed.
 *
 * This is how we can retireve mosth used paths, for common purpose:

 * api_get_path(REL_PATH)                       /chamilo/
 * api_get_path(REL_COURSE_PATH)                /chamilo/courses/
 * api_get_path(REL_CODE_PATH)                  /chamilo/main/
 * api_get_path(SYS_SERVER_ROOT_PATH)           /var/www/ - This is the physical folder where the system Chamilo has been placed. It is not always equal to $_SERVER['DOCUMENT_ROOT'].
 * api_get_path(SYS_PATH)                       /var/www/chamilo/
 * api_get_path(SYS_ARCHIVE_PATH)               /var/www/chamilo/archive/
 * api_get_path(SYS_COURSE_PATH)                /var/www/chamilo/courses/
 * api_get_path(SYS_CODE_PATH)                  /var/www/chamilo/main/
 * api_get_path(INCLUDE_PATH)                   /var/www/chamilo/main/inc/
 * api_get_path(LIBRARY_PATH)                   /var/www/chamilo/main/inc/lib/
 * api_get_path(CONFIGURATION_PATH)             /var/www/chamilo/main/inc/conf/
 * api_get_path(SYS_LANG_PATH)                  /var/www/chamilo/main/lang/
 * api_get_path(SYS_PLUGIN_PATH)                /var/www/chamilo/plugin/
 * api_get_path(SYS_TEST_PATH)                  /var/www/chamilo/tests/
 * api_get_path(SYS_TEMPLATE_PATH)              /var/www/chamilo/main/template/
 *
 * api_get_path(WEB_SERVER_ROOT_PATH)           http://www.mychamilo.org/
 * api_get_path(WEB_PATH)                       http://www.mychamilo.org/chamilo/
 * api_get_path(WEB_COURSE_PATH)                http://www.mychamilo.org/chamilo/courses/
 * api_get_path(WEB_CODE_PATH)                  http://www.mychamilo.org/chamilo/main/
 * api_get_path(WEB_PLUGIN_PATH)                http://www.mychamilo.org/chamilo/plugin/
 * api_get_path(WEB_ARCHIVE_PATH)               http://www.mychamilo.org/chamilo/archive/
 * api_get_path(WEB_IMG_PATH)                   http://www.mychamilo.org/chamilo/main/img/
 * api_get_path(WEB_CSS_PATH)                   http://www.mychamilo.org/chamilo/main/css/
 * api_get_path(WEB_LIBRARY_PATH)               http://www.mychamilo.org/chamilo/main/inc/lib/
 * api_get_path(WEB_TEMPLATE_PATH)              http://www.mychamilo.org/chamilo/main/template/
 *
 *
 * This is how we retrieve paths of "registerd" resource files (scripts, players, etc.):
 * api_get_path(TO_WEB, FLASH_PLAYER_AUDIO)     http://www.mychamilo.org/chamilo/main/inc/lib/mediaplayer/player.swf
 * api_get_path(TO_WEB, FLASH_PLAYER_VIDEO)     http://www.mychamilo.org/chamilo/main/inc/lib/mediaplayer/player.swf
 * api_get_path(TO_SYS, SCRIPT_SWFOBJECT)       /var/www/chamilo/main/inc/lib/swfobject/swfobject.js
 * api_get_path(TO_REL, SCRIPT_ASCIIMATHML)     /chamilo/main/inc/lib/asciimath/ASCIIMathML.js
 * ...
 *
 * We can convert arbitrary paths, that are not registered (no defined constant).
 * For guaranteed result, these paths should point inside the system Chamilo.
 * Some random examples:
 * api_get_path(TO_WEB, $_SERVER['REQUEST_URI'])
 * api_get_path(TO_SYS, $_SERVER['PHP_SELF'])
 * api_get_path(TO_REL, __FILE__)
 * ...
 */
function api_get_path($path_type, $path = null) {

    static $paths = array(
        WEB_PATH                => '',
        SYS_PATH                => '',
        REL_PATH                => '',
        WEB_SERVER_ROOT_PATH    => '',
        SYS_SERVER_ROOT_PATH    => '',
        WEB_COURSE_PATH         => '',
        SYS_COURSE_PATH         => '',
        REL_COURSE_PATH         => '',
        REL_CODE_PATH           => '',
        WEB_CODE_PATH           => '',
        SYS_CODE_PATH           => '',
        SYS_LANG_PATH           => 'lang/',
        WEB_IMG_PATH            => 'img/',
        WEB_CSS_PATH            => 'css/',
        SYS_PLUGIN_PATH         => 'plugin/',
        WEB_PLUGIN_PATH         => 'plugin/',
        SYS_ARCHIVE_PATH        => 'archive/',
        WEB_ARCHIVE_PATH        => 'archive/',
        INCLUDE_PATH            => 'inc/',
        LIBRARY_PATH            => 'inc/lib/',
        CONFIGURATION_PATH      => 'inc/conf/',
        WEB_LIBRARY_PATH        => 'inc/lib/',
        WEB_AJAX_PATH           => 'inc/ajax/',
        SYS_TEST_PATH           => 'tests/',
        WEB_TEMPLATE_PATH       => 'template/',
        SYS_TEMPLATE_PATH       => 'template/'
    );

    static $resource_paths = array(
        FLASH_PLAYER_AUDIO      => 'inc/lib/mediaplayer/player.swf',
        FLASH_PLAYER_VIDEO      => 'inc/lib/mediaplayer/player.swf',
        SCRIPT_SWFOBJECT        => 'inc/lib/swfobject/swfobject.js',
        SCRIPT_ASCIIMATHML      => 'inc/lib/javascript/asciimath/ASCIIMathML.js',
        DRAWING_ASCIISVG        => 'inc/lib/javascript/asciimath/d.svg'
    );

    static $is_this_function_initialized;
    static $server_base_web; // No trailing slash.
    static $server_base_sys; // No trailing slash.
    static $root_web;
    static $root_sys;
    static $root_rel;
    static $code_folder;
    static $course_folder;

    // Always load root_web modifications for multiple url features
    global $_configuration;
    //default $_configuration['root_web'] configuration
    $root_web = $_configuration['root_web'];

    // Configuration data for already installed system.
    $root_sys = $_configuration['root_sys'];
    $load_new_config = false;

    // To avoid that the api_get_access_url() function fails since global.inc.php also calls the main_api.lib.php
    if ($path_type == WEB_PATH) {
        if (isset($_configuration['access_url']) &&  $_configuration['access_url'] != 1) {
            //we look into the DB the function api_get_access_url
            $url_info = api_get_access_url($_configuration['access_url']);
            $root_web = $url_info['active'] == 1 ? $url_info['url'] : $_configuration['root_web'];
            $load_new_config = true;
        }
    }

    if (!$is_this_function_initialized) {
        global $_configuration;

        $root_rel       = $_configuration['url_append'];
        $code_folder    = $_configuration['code_append'];
        $course_folder  = $_configuration['course_folder'];

        // Support for the installation process.
        // Developers might use the function api_get_path() directly or indirectly (this is difficult to be traced), at the moment when
        // configuration has not been created yet. This is why this function should be upgraded to return correct results in this case.

        if (defined('SYSTEM_INSTALLATION') && SYSTEM_INSTALLATION) {
            if (($pos = strpos(($requested_page_rel = api_get_self()), 'main/install')) !== false) {
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
        $root_web       = api_add_trailing_slash($root_web);
        $root_sys       = api_add_trailing_slash($root_sys);
        $root_rel       = api_add_trailing_slash($root_rel);
        $code_folder    = api_add_trailing_slash($code_folder);
        $course_folder  = api_add_trailing_slash($course_folder);

        // Web server base and system server base.
        $server_base_web = preg_replace('@'.$root_rel.'$@', '', $root_web); // No trailing slash.
        $server_base_sys = preg_replace('@'.$root_rel.'$@', '', $root_sys); // No trailing slash.

        // Initialization of a table taht contains common-purpose paths.
        $paths[WEB_PATH]                = $root_web;
        $paths[SYS_PATH]                = $root_sys;
        $paths[REL_PATH]                = $root_rel;
        $paths[WEB_SERVER_ROOT_PATH]    = $server_base_web.'/';
        $paths[SYS_SERVER_ROOT_PATH]    = $server_base_sys.'/';
        $paths[WEB_COURSE_PATH]         = $root_web.$course_folder;
        $paths[SYS_COURSE_PATH]         = $root_sys.$course_folder;
        $paths[REL_COURSE_PATH]         = $root_rel.$course_folder;
        $paths[REL_CODE_PATH]           = $root_rel.$code_folder;
        $paths[WEB_CODE_PATH]           = $root_web.$code_folder;
        $paths[SYS_CODE_PATH]           = $root_sys.$code_folder;

        // Now we can switch into api_get_path() "terminology".
        $paths[SYS_LANG_PATH]           = $paths[SYS_CODE_PATH].$paths[SYS_LANG_PATH];
        $paths[SYS_PLUGIN_PATH]         = $paths[SYS_PATH].$paths[SYS_PLUGIN_PATH];
        $paths[SYS_ARCHIVE_PATH]        = $paths[SYS_PATH].$paths[SYS_ARCHIVE_PATH];
        $paths[SYS_TEST_PATH]           = $paths[SYS_PATH].$paths[SYS_TEST_PATH];
        $paths[SYS_TEMPLATE_PATH]       = $paths[SYS_CODE_PATH].$paths[SYS_TEMPLATE_PATH];

        $paths[WEB_CSS_PATH]            = $paths[WEB_CODE_PATH].$paths[WEB_CSS_PATH];
        $paths[WEB_IMG_PATH]            = $paths[WEB_CODE_PATH].$paths[WEB_IMG_PATH];
        $paths[WEB_LIBRARY_PATH]        = $paths[WEB_CODE_PATH].$paths[WEB_LIBRARY_PATH];
        $paths[WEB_AJAX_PATH]           = $paths[WEB_CODE_PATH].$paths[WEB_AJAX_PATH];

        $paths[WEB_PLUGIN_PATH]         = $paths[WEB_PATH].$paths[WEB_PLUGIN_PATH];
        $paths[WEB_ARCHIVE_PATH]        = $paths[WEB_PATH].$paths[WEB_ARCHIVE_PATH];

        $paths[WEB_TEMPLATE_PATH]       = $paths[WEB_CODE_PATH].$paths[WEB_TEMPLATE_PATH];

        $paths[INCLUDE_PATH]            = $paths[SYS_CODE_PATH].$paths[INCLUDE_PATH];
        $paths[LIBRARY_PATH]            = $paths[SYS_CODE_PATH].$paths[LIBRARY_PATH];
        $paths[CONFIGURATION_PATH]      = $paths[SYS_CODE_PATH].$paths[CONFIGURATION_PATH];

        $is_this_function_initialized = true;
    } else {
        if ($load_new_config) {
            //  Redefining variables to work well with the "multiple url" feature

            // All web paths need to be here
            $web_paths = array(
                WEB_PATH                => '',
                WEB_SERVER_ROOT_PATH    => '',
                WEB_COURSE_PATH         => '',
                WEB_CODE_PATH           => '',
                WEB_IMG_PATH            => 'img/',
                WEB_CSS_PATH            => 'css/',
                WEB_PLUGIN_PATH         => 'plugin/',
                WEB_ARCHIVE_PATH        => 'archive/',
                WEB_LIBRARY_PATH        => 'inc/lib/',
                WEB_AJAX_PATH           => 'inc/ajax/',
            );

            $root_web = api_add_trailing_slash($root_web);
            // Web server base and system server base.
            $server_base_web = preg_replace('@'.$root_rel.'$@', '', $root_web); // No trailing slash.

            // Redefine root webs
            $paths[WEB_PATH]                = $root_web;
            $paths[WEB_SERVER_ROOT_PATH]    = $server_base_web.'/';
            $paths[WEB_COURSE_PATH]         = $root_web.$course_folder;
            $paths[WEB_CODE_PATH]           = $root_web.$code_folder;
            $paths[WEB_IMG_PATH]            = $paths[WEB_CODE_PATH].$web_paths[WEB_IMG_PATH];

            $paths[WEB_CSS_PATH]            = $paths[WEB_CODE_PATH].$web_paths[WEB_CSS_PATH];
            $paths[WEB_PLUGIN_PATH]         = $paths[WEB_PATH].$web_paths[WEB_PLUGIN_PATH];
            $paths[WEB_ARCHIVE_PATH]        = $paths[WEB_PATH].$web_paths[WEB_ARCHIVE_PATH];
            $paths[WEB_LIBRARY_PATH]        = $paths[WEB_CODE_PATH].$web_paths[WEB_LIBRARY_PATH];
            $paths[WEB_AJAX_PATH]           = $paths[WEB_CODE_PATH].$web_paths[WEB_AJAX_PATH];
        }
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

    // Detection of the input path type. Conversion to semi-absolute type ( /chamilo/main/inc/.... ).

    if (preg_match(VALID_WEB_PATH, $path)) {

        // A special case: When a URL points to the document download script directly, without
        // mod-rewrite translation, we have to translate it into an "ordinary" web path.
        // For example:
        // http://localhost/chamilo/main/document/download.php?doc_url=/image.png&cDir=/
        // becomes
        // http://localhost/chamilo/courses/TEST/document/image.png
        // TEST is a course directory name, so called "system course code".
        if (strpos($path, 'download.php') !== false) { // Fast detection first.
            $path = urldecode($path);
            if (preg_match('/(.*)main\/document\/download.php\?doc_url=\/(.*)&cDir=\/(.*)?/', $path, $matches)) {
                $sys_course_code =
                    isset($_SESSION['_course']['sysCode'])  // User is inside a course?
                        ? $_SESSION['_course']['sysCode']   // Yes, then use course's directory name.
                        : '{SYS_COURSE_CODE}';              // No, then use a fake code, it may be processed later.
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
 * @param string $path      The path to be tesed. It should be full path, web-absolute (WEB), semi-absolute (REL) or system-absolyte (SYS).
 * @return bool             Returns true when the given path is inside the system, false otherwise.
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
 * @param string $path          The input path.
 * @return string               Returns the modified path.
 */
function api_add_trailing_slash($path) {
    return substr($path, -1) == '/' ? $path : $path.'/';
}

/**
 * Removes from a given path the trailing slash if it is necessary (removes "/" character from the end of the string).
 * @param string $path          The input path.
 * @return string               Returns the modified path.
 */
function api_remove_trailing_slash($path) {
    return substr($path, -1) == '/' ? substr($path, 0, -1) : $path;
}

/**
 * Checks the RFC 3986 syntax of a given URL.
 * @param string $url       The URL to be checked.
 * @param bool $absolute    Whether the URL is absolute (beginning with a scheme such as "http:").
 * @return bool             Returns the URL if it is valid, FALSE otherwise.
 * This function is an adaptation from the function valid_url(), Drupal CMS.
 * @link http://drupal.org
 * Note: The built-in function filter_var($urs, FILTER_VALIDATE_URL) has a bug for some versions of PHP.
 * @link http://bugs.php.net/51192
 */
function api_valid_url($url, $absolute = false) {
    if ($absolute) {
        if (preg_match("
            /^                                                      # Start at the beginning of the text
            (?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
            (?:                                                     # Userinfo (optional) which is typically
                (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*    # a username or a username and password
                (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@        # combination
            )?
            (?:
                (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                      # A domain name or a IPv4 address
                |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])       # or a well formed IPv6 address
            )
            (?::[0-9]+)?                                            # Server port number (optional)
            (?:[\/|\?]
                (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2}) # The path and query (optional)
            *)?
            $/xi", $url)) {
            return $url;
        }
        return false;
    } else {
        return preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url) ? $url : false;
    }
}

/**
 * Checks whether a given string looks roughly like an email address.
 * Tries to use PHP built-in validator in the filter extension (from PHP 5.2), falls back to a reasonably competent regex validator.
 * Conforms approximately to RFC2822
 * @link http://www.hexillion.com/samples/#Regex Original pattern found here
 * This function is an adaptation from the method PHPMailer::ValidateAddress(), PHPMailer module.
 * @link http://phpmailer.worxware.com
 * @param string $address   The e-mail address to be checked.
 * @return mixed            Returns the e-mail if it is valid, FALSE otherwise.
 */
function api_valid_email($address) {
    // disable for now because the results are incoherent - YW 20110926
    if (function_exists('filter_var')) { // Introduced in PHP 5.2.
        return filter_var($address, FILTER_VALIDATE_EMAIL);
    } else {
        return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address) ? $address : false;
    }
}


/* PROTECTION FUNCTIONS
   Use these functions to protect your scripts. */

/**
 * Function used to protect a course script.
 * The function blocks access when
 * - there is no $_SESSION["_course"] defined; or
 * - $is_allowed_in_course is set to false (this depends on the course
 * visibility and user status).
 *
 * This is only the first proposal, test and improve!
 * @param boolean       Option to print headers when displaying error message. Default: false
 * @return boolean      True if the user has access to the current course or is out of a course context, false otherwise
 * @todo replace global variable
 * @author Roan Embrechts
 */
function api_protect_course_script($print_headers = false) {
    global $is_allowed_in_course;
    $is_visible = false;
    if (api_is_platform_admin()) {
    	return true;
    }
    $course_info = api_get_course_info();
    if (isset($course_info) && !empty($course_info['visibility'])) {
    	switch($course_info['visibility']) {
    		default:
    		case 0: //Completely closed: the course is only accessible to the teachers.
    			if (api_get_user_id() && !api_is_anonymous() && (api_is_allowed_to_edit())) {
    				$is_visible = true;
    			}
    			break;    		
    		case 1: //Private - access authorized to course members only
    			if (api_get_user_id() && !api_is_anonymous() && $is_allowed_in_course) {
    				$is_visible = true;
    			}
    			break;   			
    		case 2: // Open - access allowed for users registered on the platform
    			if (api_get_user_id() && !api_is_anonymous()) {
    				$is_visible = true;    				
    			}    			
    			break;
    		case 3: //Open - access allowed for the whole world
    			$is_visible = true;    			
    			break;
    	}
    }    	    	
        
    if (!$is_visible) {
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
        api_not_allowed(true);
        return false;
    }
    return true;
}

/**
 * Function used to prevent anonymous users from accessing a script.
 *
 * @author Roan Embrechts
 */
function api_block_anonymous_users($print_headers = true) {
    global $_user;
    if (!(isset($_user['user_id']) && $_user['user_id']) || api_is_anonymous($_user['user_id'], true)) {        
        api_not_allowed($print_headers);
        return false;
    }
    return true;
}


/* ACCESSOR FUNCTIONS
   Don't access kernel variables directly, use these functions instead. */

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
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
        $navigator = 'Chrome';
        list (, $version) = explode('Chrome', $_SERVER['HTTP_USER_AGENT']);   
             
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Gecko') !== false) {
        $navigator = 'Mozilla';        
        list (, $version) = explode('; rv:', $_SERVER['HTTP_USER_AGENT']);        
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape') !== false) {
        $navigator = 'Netscape';
        list (, $version) = explode('Netscape', $_SERVER['HTTP_USER_AGENT']);
    } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Konqueror') !== false) {
        $navigator = 'Konqueror';
        list (, $version) = explode('Konqueror', $_SERVER['HTTP_USER_AGENT']);
    } elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'applewebkit') !== false) {
        $navigator = 'AppleWebKit';        
        list (, $version) = explode('Version/', $_SERVER['HTTP_USER_AGENT']);        
    } elseif (stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false) {
        $navigator = 'Safari';
        list (, $version) = explode('Version/', $_SERVER['HTTP_USER_AGENT']);
    }
    $version = str_replace('/', '', $version);    
    if (strpos($version, '.') === false) {
        $version = number_format(doubleval($version), 1);
    }    
    $return_array = array ('name' => $navigator, 'version' => $version);        
    return $return_array;
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
 * example: The function can be used to check if a user is logged in
 *          if (api_get_user_id())
 * @return integer the id of the current user, 0 if is empty
 */
function api_get_user_id() {
    return empty($GLOBALS['_user']['user_id']) ? 0 : intval($GLOBALS['_user']['user_id']);
}

/**
 * Gets the list of courses a specific user is subscribed to
 * @param int       User ID
 * @param boolean   Whether to get session courses or not - NOT YET IMPLEMENTED
 * @return array    Array of courses in the form [0]=>('code'=>xxx,'db'=>xxx,'dir'=>xxx,'status'=>d)
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
                                    AND   cu.user_id = '".$userid."' AND cu.relation_type<>".COURSE_RELATION_TYPE_RRHH." ";
    $result = Database::query($sql_select_courses);
    if ($result === false) { return array(); }
    while ($row = Database::fetch_array($result)) {
        // we only need the database name of the course
        $courses[] = $row;
    }
    return $courses;
}

/**
 * Formats user information into a standard array
 *
 * @param array Non-standard user array
 * @return array Standard user array
 */
function _api_format_user($user) {
    $result = array();
    if (isset($user['firstname']) && isset($user['lastname'])) {
        $firstname = $user['firstname'];
        $lastname = $user['lastname'];
    } elseif (isset($user['firstName']) && isset($user['lastName'])) {
        $firstname = $user['firstName'];
        $lastname = $user['lastName'];
    }
    
    $result['complete_name'] 	= api_get_person_name($firstname, $lastname);

    $result['firstname'] 		= $firstname;
    $result['lastname'] 		= $lastname;

    // Kept for historical reasons
    $result['firstName'] 		= $firstname;
    $result['lastName'] 		= $lastname;

    if (isset($user['email'])) {
        $result['mail'] = $user['email'];
    } else {
        $result['mail'] = $user['mail'];
    }
    $user_id = intval($user['user_id']);
    $result['picture_uri']      = $user['picture_uri'];
    $result['user_id']          = $user_id;
    $result['official_code']    = $user['official_code'];
    $result['status']           = $user['status'];
    $result['auth_source']      = $user['auth_source'];
    
    if (isset($user['username'])) {
        $result['username']         = $user['username'];
    }
    
    $result['theme']            = $user['theme'];
    $result['language']         = $user['language'];
    if (!isset($user['lastLogin']) && !isset($user['last_login'])) {
        require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
        $timestamp = Tracking::get_last_connection_date($result['user_id'], false, true);
        // Convert the timestamp back into a datetime
        // NOTE: this timestamp has ALREADY been converted to the local timezone in the get_last_connection_date function
        $last_login = date('Y-m-d H:i:s', $timestamp);
    } else {
        if (isset($user['lastLogin'])) {
            $last_login = $user['lastLogin'];
        } else {
            $last_login = $user['last_login'];
        }
    }
    $result['last_login'] = $last_login;
    // Kept for historical reasons
    $result['lastLogin'] = $last_login;
    
    //Getting user avatar
    
	$picture_filename = trim($user['picture_uri']);
	$avatar = api_get_path(WEB_CODE_PATH).'img/unknown.jpg';
	$avatar_small = api_get_path(WEB_CODE_PATH).'img/unknown_22.jpg';
	$dir = 'upload/users/'.$user_id.'/';
	if (!empty($picture_filename) && api_is_anonymous() ) {
		if (api_get_setting('split_users_upload_directory') === 'true') {			
			$dir = 'upload/users/'.substr((string)$user_id, 0, 1).'/'.$user_id.'/';			
		}
	}
	$image_sys_path = api_get_path(SYS_CODE_PATH).$dir.$picture_filename;
	if (file_exists($image_sys_path) && !is_dir($image_sys_path)) {
		$avatar = api_get_path(WEB_CODE_PATH).$dir.$picture_filename;
		$avatar_small = api_get_path(WEB_CODE_PATH).$dir.'small_'.$picture_filename;
	}    
	
    $result['avatar'] = $avatar;
    $result['avatar_small'] = $avatar_small;
	if (isset($user['user_is_online'])) {
		$result['user_is_online'] = $user['user_is_online'] == true ? 1 : 0;
	}	
    return $result;
}

/**
 * Finds all the information about a user. If no paramater is passed you find all the information about the current user.
 * @param $user_id (integer): the id of the user
 * @return $user_info (array): user_id, lastname, firstname, username, email, ...
 * @author Patrick Cool <patrick.cool@UGent.be>
 * @version 21 September 2004
 */
function api_get_user_info($user_id = '', $check_if_user_is_online = false) {    
    if ($user_id == '') {
        return _api_format_user($GLOBALS['_user']);
    }
    $sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_USER)." WHERE user_id='".Database::escape_string($user_id)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $result_array = Database::fetch_array($result);
		if ($check_if_user_is_online) {
			$result_array['user_is_online'] = user_is_online($user_id);
		}
        return _api_format_user($result_array);
    }
	
    return false;
}

/**
 * Finds all the information about a user from username instead of user id
 * @param $username (string): the username
 * @return $user_info (array): user_id, lastname, firstname, username, email, ...
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
function api_get_user_info_from_username($username = '') {
    if (empty($username)) { return false; }    
    $sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_USER)." WHERE username='".Database::escape_string($username)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $result_array = Database::fetch_array($result);
        return _api_format_user($result_array);
    }
    return false;
}

/**
 * @TODO This function should be the real id (integer)
 * Returns the current course code (string)
 */
function api_get_course_id() {
    return $GLOBALS['_cid'];
}

/**
 * Returns the current course id (integer)
 */
function api_get_real_course_id() {
    return isset($_SESSION['_real_cid']) ? intval($_SESSION['_real_cid']) : 0;
}

/**
 * Returns the current course id (integer)
 */
function api_get_course_int_id() {	
    return isset($_SESSION['_real_cid']) ? intval($_SESSION['_real_cid']) : 0;
}


/**
 * Returns the current course directory
 *
 * This function relies on api_get_course_info()
 * @param string    The course code - optional (takes it from session if not given)
 * @return string   The directory where the course is located inside the Chamilo "courses" directory
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
function api_get_course_path($course_code = null) {
    $info = !empty($course_code) ? api_get_course_info($course_code) : api_get_course_info();
    return $info['path'];
}

/**
 * Gets a course setting from the current course_setting table. Try always using integer values.
 * @param string    The name of the setting we want from the table
 * @param string    Optional: course code
 * @return mixed    The value of that setting in that table. Return -1 if not found.
 */
function api_get_course_setting($setting_name, $course_code = null) {    
    $course_info = api_get_course_info($course_code);    
	$table 		 = Database::get_course_table(TABLE_COURSE_SETTING);    
    $setting_name = Database::escape_string($setting_name);
    if (!empty($course_info['real_id']) && !empty($setting_name)) {
        $sql = "SELECT value FROM $table WHERE c_id = {$course_info['real_id']} AND variable = '$setting_name'";    
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            return $row['value'];
        }
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
 * @return int  User ID of the anonymous user, or O if no anonymous user found
 */
function api_get_anonymous_id() {
    $table = Database::get_main_table(TABLE_MAIN_USER);
    $sql = "SELECT user_id FROM $table WHERE status = 6";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_array($res);
        //error_log('api_get_anonymous_id() returns '.$row['user_id'], 0);
        return $row['user_id'];
    }
    // No anonymous user was found.
    return 0;
}

/**
 * Returns the cidreq parameter name + current course id taken from 
 * $GLOBALS['_cid'] and returns a string like 'cidReq=ABC&id_session=123
 * @return  string  Course & session references to add to a URL
 */
function api_get_cidreq() {
    return empty($GLOBALS['_cid']) ? '' : 'cidReq='.htmlspecialchars($GLOBALS['_cid']).
            (api_get_session_id() == 0 ? '&amp;id_session=0' : '&amp;id_session='.api_get_session_id()).
            (api_get_group_id() == 0 ? '&amp;gidReq=0' : '&amp;gidReq='.api_get_group_id());
}
/**
 * Returns the current course info array.
 * Note: this array is only defined if the user is inside a course.
 * Array elements:
 * ['name']
 * ['official_code']
 * ['sysCode']
 * ['path']
 * ['dbName']
 * ['dbNameGlu']
 * ['titular']
 * ['language']
 * ['extLink']['url' ]
 * ['extLink']['name']
 * ['categoryCode']
 * ['categoryName']
 *
 * Now if the course_code is given, the returned array gives info about that
 * particular course, not specially the current one.
 * @todo    Same behaviour as api_get_user_info so that api_get_course_id becomes absolete too.
 */
function api_get_course_info($course_code = null) {
    if (!empty($course_code)) {
        //global $_course;var_dump($_course);
        $course_code        = Database::escape_string($course_code);
        $course_table       = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_cat_table   = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT course.*, course_category.code faCode, course_category.name faName
                 FROM $course_table
                 LEFT JOIN $course_cat_table
                 ON course.category_code =  course_category.code
                 WHERE course.code = '$course_code'";
        $result = Database::query($sql);
        $_course = array();
        if (Database::num_rows($result) > 0) {
            global $_configuration;
            $course_data = Database::fetch_array($result);
            $_course['id'           ]         = $course_data['code'           ];
            $_course['code'         ]         = $course_data['code'           ];
            $_course['name'         ]         = $course_data['title'          ];
            $_course['official_code']         = $course_data['visual_code'    ]; // Use in echo statements.
            $_course['sysCode'      ]         = $course_data['code'           ]; // Use as key in db.
            $_course['path'         ]         = $course_data['directory'      ]; // Use as key in path.
            $_course['directory'    ]         = $course_data['directory'      ];
            
            //@todo should be deprecated
            $_course['dbName'       ]         = $course_data['db_name'        ]; // Use as key in db list.
            $_course['db_name'      ]         = $course_data['db_name'];         // 
            $_course['dbNameGlu'    ]         = $_configuration['table_prefix'] . $course_data['db_name'] . $_configuration['db_glue']; // Use in all queries.
            
            $_course['titular'      ]         = $course_data['tutor_name'     ];
            $_course['language'     ]         = $course_data['course_language'];
            $_course['extLink'      ]['url' ] = $course_data['department_url' ];
            $_course['extLink'      ]['name'] = $course_data['department_name'];
            $_course['categoryCode' ]         = $course_data['faCode'         ];
            $_course['categoryName' ]         = $course_data['faName'         ];
            $_course['visibility'   ]         = $course_data['visibility'     ];
            $_course['subscribe_allowed']     = $course_data['subscribe'      ];
            $_course['unubscribe_allowed']    = $course_data['unsubscribe'    ];
            
            $_course['department_name']       = $course_data['department_name'];
            $_course['department_url']        = $course_data['department_url' ];

            // The real_id is an integer. It is mandatory for future implementations.
            $_course['real_id'     ]          = $course_data['id'              ];
            $_course['course_language']       = $course_data['course_language'];
			
			//I know this is bad, but this reflects that it was a bad decistion to not add a flag in the DB if an image exists			
			if (file_exists(api_get_path(SYS_COURSE_PATH).$course_data['directory'].'/course-pic85x85.png')) {
				$url_image = api_get_path(WEB_COURSE_PATH).$course_data['directory'].'/course-pic85x85.png';
			} else {
				$url_image = api_get_path(WEB_IMG_PATH).'without_picture.png';
			}
			$_course['course_image'] = $url_image;
        }
        return $_course;
    }
    global $_course;
    if ($_course == '-1') $_course = array();
    return $_course;
}


/**
 * Returns the current course info array.

 * Now if the course_code is given, the returned array gives info about that
 * particular course, not specially the current one.
 */
function api_get_course_info_by_id($id = null) {
    if (!empty($id)) {
        $id = intval($id);
        $course_table       = Database::get_main_table(TABLE_MAIN_COURSE);
        $course_cat_table   = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $sql = "SELECT course.*, course_category.code faCode, course_category.name faName
                 FROM $course_table
                 LEFT JOIN $course_cat_table
                 ON course.category_code =  course_category.code
                 WHERE course.id = $id";
        $result = Database::query($sql);
        $_course = array();
        if (Database::num_rows($result) > 0) {
            global $_configuration;
            $course_data = Database::fetch_array($result);

            $_course['id'           ]         = $course_data['code'           ];
            // Added
            $_course['code'         ]         = $course_data['code'           ];
            $_course['name'         ]         = $course_data['title'          ];
            $_course['official_code']         = $course_data['visual_code'    ]; // Use in echo statements.
            $_course['sysCode'      ]         = $course_data['code'           ]; // Use as key in db.
            $_course['path'         ]         = $course_data['directory'      ]; // Use as key in path.
            $_course['directory'    ]         = $course_data['directory'      ];
            
            //@todo should be deprecated
            $_course['dbName'       ]         = $course_data['db_name'        ]; // Use as key in db list.
            $_course['db_name'      ]         = $course_data['db_name'         ];            
            $_course['dbNameGlu'    ]         = $_configuration['table_prefix'] . $course_data['db_name'] . $_configuration['db_glue']; // Use in all queries.
            
            $_course['titular'      ]         = $course_data['tutor_name'     ];
            $_course['language'     ]         = $course_data['course_language'];
            $_course['extLink'      ]['url' ] = $course_data['department_url' ];
            $_course['extLink'      ]['name'] = $course_data['department_name'];
            
            $_course['categoryCode' ]         = $course_data['faCode'         ];
            $_course['categoryName' ]         = $course_data['faName'         ];

            $_course['visibility'   ]         = $course_data['visibility'      ];
            $_course['subscribe_allowed']     = $course_data['subscribe'       ];
            $_course['unubscribe_allowed']    = $course_data['unsubscribe'     ];

            $_course['real_id'      ]         = $course_data['id'              ];            
            $_course['title'        ]         = $course_data['title'           ];
            $_course['course_language']       = $course_data['course_language'];
			
			if (file_exists(api_get_path(SYS_COURSE_PATH).$course_data['directory'].'/course-pic85x85.png')) {
				$url_image = api_get_path(WEB_COURSE_PATH).$course_data['directory'].'/course-pic85x85.png';
			} else {
				$url_image = api_get_path(WEB_IMG_PATH).'without_picture.png';
			}
			$_course['course_image'] = $url_image;

        }
        return $_course;
    }
    //global $_course;
    global $_course;
    if ($_course == '-1') $_course = array();
    return $_course;
}


/* SESSION MANAGEMENT */

/**
 * Starts the Chamilo session.
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
    global $_configuration;

    /* Causes too many problems and is not configurable dynamically.
    if ($already_installed) {
        $session_lifetime = 360000;
        if (isset($_configuration['session_lifetime'])) {
            $session_lifetime = $_configuration['session_lifetime'];
        }
        //session_set_cookie_params($session_lifetime,api_get_path(REL_PATH));
    }
    */

    if (!isset($_configuration['session_stored_in_db'])) {
        $_configuration['session_stored_in_db'] = false;
    }
    if ($_configuration['session_stored_in_db'] && function_exists('session_set_save_handler')) {
        require_once api_get_path(LIBRARY_PATH).'session_handler.class.php';
        $session_handler = new session_handler();
        @session_set_save_handler(array(& $session_handler, 'open'), array(& $session_handler, 'close'), array(& $session_handler, 'read'), array(& $session_handler, 'write'), array(& $session_handler, 'destroy'), array(& $session_handler, 'garbage'));
    }
    
    /* 
     * Prevent Session fixation bug fixes  
     * See http://support.chamilo.org/issues/3600
     * http://php.net/manual/en/session.configuration.php
     * @todo use session_set_cookie_params with some custom admin parameters
     */
        
    //session.cookie_lifetime
    
    //the session ID is only accepted from a cookie
    ini_set('session.use_only_cookies', 1);
    
    //HTTPS only if possible 
    //ini_set('session.cookie_secure', 1);
    
    //session ID in the cookie is only readable by the server 
    ini_set('session.cookie_httponly', 1);
    
    //Use entropy file    
    //session.entropy_file
    //ini_set('session.entropy_length', 128);
    
    //Do not include the identifier in the URL, and not to read the URL for identifiers.
    ini_set('session.use_trans_sid', 0);    
   
    session_name('ch_sid');    
    session_start();
    
    if (!isset($_SESSION['starttime'])) {
        $_SESSION['starttime'] = time();
    }

    if ($already_installed) {
        if (empty($_SESSION['checkDokeosURL'])) {
            $_SESSION['checkDokeosURL'] = api_get_path(WEB_PATH);
            //$_SESSION['session_expiry'] = time() + $session_lifetime; // It is useless at the moment.
        } elseif ($_SESSION['checkDokeosURL'] != api_get_path(WEB_PATH)) {
            api_session_clear();
            //$_SESSION['session_expiry'] = time() + $session_lifetime;
        }
    }
    if ( isset($_SESSION['starttime']) && $_SESSION['starttime'] < time() - $_configuration['session_lifetime'] ) {
        $_SESSION['starttime'] = time();
    }
}

/**
 * Saves a variable into the session
 *
 * BUG: function works only with global variables
 *
 * @author Olivier Brouckaert
 * @param  string variable - the variable name to save into the session
 */
function api_session_register($variable) {
    global $$variable;
    $_SESSION[$variable] = $$variable;
}

/**
 * Removes a variable from the session.
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
        unset($_SESSION[$variable]);
    }
}

/**
 * Clears the session
 *
 * @author Olivier Brouckaert
 */
function api_session_clear() {
    session_regenerate_id();
    session_unset();    
    $_SESSION = array();
}

/**
 * Destroys the session
 *
 * @author Olivier Brouckaert
 */
function api_session_destroy() {
    session_unset();
    $_SESSION = array();
    session_destroy();
}


/* STRING MANAGEMENT */

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
 * Notes about what a password "OK to use" is:
 * 1. The password should be at least 5 characters long.
 * 2. Only English letters (uppercase or lowercase, it doesn't matter) and digits are allowed.
 * 3. The password should contain at least 3 letters.
 * 4. It should contain at least 2 digits.
 * 5. It should not contain 3 or more consequent (according to ASCII table) characters.
 */
function api_check_password($password) {
    $password_length = api_strlen($password);
    if ($password_length < 5) {
        return false;
    }
    $password = api_strtolower($password);
    $letters = 0;
    $digits = 0;
    $consequent_characters = 0;
    $previous_character_code = 0;
    for ($i = 0; $i < $password_length; $i ++) {
        $current_character_code = api_ord(api_substr($password, $i, 1));
        if ($i && abs($current_character_code - $previous_character_code) <= 1) {
            $consequent_characters ++;
            if ($consequent_characters == 3) {
                return false;
            }
        } else {
            $consequent_characters = 1;
        }
        if ($current_character_code >= 97 && $current_character_code <= 122) {
            $letters ++;
        } elseif ($current_character_code >= 48 && $current_character_code <= 57) {
            $digits ++;
        } else {
            return false;
        }
        $previous_character_code = $current_character_code;
    }
    return ($letters >= 3 && $digits >= 2);
}

/**
 * Clears the user ID from the session if it was the anonymous user. Generally
 * used on out-of-tools pages to remove a user ID that could otherwise be used
 * in the wrong context.
 * This function is to be used in conjunction with the api_set_anonymous()
 * function to simulate the user existence in case of an anonymous visit.
 * @param bool      database check switch - passed to api_is_anonymous()
 * @return bool     true if succesfully unregistered, false if not anonymous.
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


/* FAILURE MANAGEMENT */

/**
 * The Failure Management module is here to compensate
 * the absence of an 'exception' device in PHP 4.
 */

/**
 * $api_failureList - array containing all the failure recorded in order of arrival.
 */
$api_failureList = array();

/**
 * Fills a global array called $api_failureList
 * This array collects all the failure occuring during the script runs
 * The main purpose is allowing to manage the display messages externaly
 * from the functions or objects. This strengthens encupsalation principle
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @param  string $failure_type - the type of failure
 * global: array $api_failureList
 * @return bolean false to stay consistent with the main script
 */
function api_set_failure($failure_type) {
    global $api_failureList;
    $api_failureList[] = $failure_type;
    return false;
}

/**
 * Sets the current user as anonymous if it hasn't been identified yet. This
 * function should be used inside a tool only. The function api_clear_anonymous()
 * acts in the opposite direction by clearing the anonymous user's data every
 * time we get on a course homepage or on a neutral page (index, admin, my space)
 * @return bool     true if set user as anonymous, false if user was already logged in or anonymous id could not be found
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
 * Gets the last failure stored in $api_failureList;
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
 * Collects and manages failures occuring during script execution
 * The main purpose is allowing to manage the display messages externaly
 * from functions or objects. This strengthens encupsalation principle
 *
 * @author Hugues Peeters <hugues.peeters@claroline.net>
 * @package chamilo.library
 */
class api_failure {

    // TODO: $api_failureList to be hidden from global scope and to be renamed according to our coding conventions.
    /**
     * IMPLEMENTATION NOTE : For now the $api_failureList list is set to the
     * global scope, as PHP 4 is unable to manage static variable in class. But
     * this feature is awaited in PHP 5. The class is already written to minize
     * the change when static class variable will be possible. And the API won't
     * change.
     */
    public $api_failureList = array();

    /**
     * Piles the last failure in the failure list
     *
     * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
     * @param  string $failure_type - the type of failure
     * @global array  $api_failureList
     * @return bolean false to stay consistent with the main script
     */
    function set_failure($failure_type) {
        global $api_failureList;
        $api_failureList[] = $failure_type;
        return false;
    }

    /**
     * Gets the last failure stored
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


/* CONFIGURATION SETTINGS */

/**
 * Gets the current Chamilo (not PHP/cookie) session ID
 * @return  int     O if no active session, the session ID otherwise
 */
function api_get_session_id() {
    return empty($_SESSION['id_session']) ? 0 : intval($_SESSION['id_session']);
}

/**
 * Gets the current Chamilo (not social network) group ID
 * @return  int     O if no active session, the session ID otherwise
 */
function api_get_group_id() {
    return empty($_SESSION['_gid']) ? 0 : intval($_SESSION['_gid']);
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
    $r = Database::query($s);
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
 * @param int       Session ID
 * @return array    information of the session
 */
function api_get_session_info($session_id) {
    $data = array();
    if (!empty($session_id)) {
        $sesion_id = intval(Database::escape_string($session_id));
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
        $sql = "SELECT * FROM $tbl_session WHERE id = $session_id";
        $result = Database::query($sql);

        if (Database::num_rows($result)>0) {
            $data = Database::fetch_array($result, 'ASSOC');
        }
    }
    return $data;
}

/**
 * Gets the session visibility by session id
 * @param int       session id
 * @return int      0 = session still available, SESSION_VISIBLE_READ_ONLY = 1, SESSION_VISIBLE = 2, SESSION_INVISIBLE = 3
 */
function api_get_session_visibility($session_id) {
    $visibility = 0; //means that the session is still available
    if (!empty($session_id)) {
        $sesion_id = intval(Database::escape_string($session_id));
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);

        $is_coach = api_is_coach();

        $condition_date_end = "";
        if ($is_coach) {
            $condition_date_end = " AND (CURDATE() > (SELECT adddate(date_end,nb_days_access_after_end) FROM $tbl_session WHERE id = $session_id) AND date_end != '0000-00-00') ";
        } else {
            $condition_date_end = " AND (date_end < CURDATE() AND date_end != '0000-00-00') ";
        }

        $sql = "SELECT visibility FROM $tbl_session
                WHERE id = $session_id $condition_date_end "; // session is old and is not unlimited

        $result = Database::query($sql);

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
 * @param int       session id
 * @param string    Chamilo course code
 * @param int       user id
 * @return int      0= Session available (in date), SESSION_VISIBLE_READ_ONLY = 1, SESSION_VISIBLE = 2, SESSION_INVISIBLE = 3
 */
function api_get_session_visibility_by_user($session_id, $course_code, $user_id) {
    $visibility = 0; // Means that the session is still available.
    if (!empty($session_id) && !empty($user_id)){
        $sesion_id      = intval(Database::escape_string($session_id));
        $user_id        = intval(Database::escape_string($user_id));
        $course_code    = Database::escape_string($course_code);
        $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION_REL_COURSE_REL_USER);
        $sql = "SELECT visibility FROM $tbl_session
                WHERE id_session = $session_id AND id_user = $user_id AND course_code = '$course_code'"; // session old
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_array($result, 'ASSOC');
            $visibility = $row['visibility'];
        } else {
            $visibility = 0;
        }
    }
    return $visibility;
}

/**
 * This function returns a (star) session icon if the session is not null and 
 * the user is not a student 
 * @param int       Session id
 * @param int       User status id - if 5 (student), will return empty
 * @return string   Session icon
 */
function api_get_session_image($session_id, $status_id) {
    $session_id = (int)$session_id;
    $session_img = '';
    if ((int)$status_id != 5) { //check whether is not a student
        if ($session_id > 0) {
            $session_img = "&nbsp;&nbsp;".Display::return_icon('star.png', get_lang('SessionSpecificResource'), array('align' => 'absmiddle'), 22);
        }
    }
    return $session_img;
}

/**
 * This function add an additional condition according to the session of the course
 * @param int       session id
 * @param bool      optional, true if more than one condition false if the only condition in the query
 * @param bool      optional, true to accept content with session=0 as well, false for strict session condition
 * @return string   condition of the session
 */
function api_get_session_condition($session_id, $and = true, $with_base_content = false) {
    $session_id = intval($session_id);
    //condition to show resources by session
    $condition_session = '';
    $condition_add = $and ? " AND " : " WHERE ";

    if ($with_base_content) {
        $condition_session = $condition_add." (session_id = $session_id OR session_id = 0) ";
    } else {
        $condition_session = $condition_add." session_id = $session_id ";
    }

    return $condition_session;
}

/**
 * This function returns information about coachs from a course in session
 * @param int       - optional, session id
 * @param string    - optional, course code
 * @return array    - array containing user_id, lastname, firstname, username.
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

    $tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);
    $tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $coaches = array();

    $sql = "SELECT u.user_id,u.lastname,u.firstname,u.username FROM $tbl_user u,$tbl_session_course_user scu
            WHERE u.user_id = scu.id_user AND scu.id_session = '$session_id' AND scu.course_code = '$course_code' AND scu.status = 2";
    $rs = Database::query($sql);

    if (Database::num_rows($rs) > 0) {

        while ($row = Database::fetch_array($rs)) {
            $coaches[] = $row;
        }
        return $coaches;
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
 * @param string    The variable name
 * @param string    The subkey (sub-variable) if any. Defaults to NULL
 * @author René Haentjens
 * @author Bart Mollet
 */
function api_get_setting($variable, $key = null) {
    global $_setting;    
    if ($variable == 'header_extra_content') {    	
        $filename = api_get_path(SYS_PATH).api_get_home_path().'header_extra_content.txt';
        if (file_exists($filename)) {
            $value = file_get_contents($filename);
            return $value ;
        } else {
            return '';
        }
    }
    if ($variable == 'footer_extra_content') {    	
        $filename = api_get_path(SYS_PATH).api_get_home_path().'footer_extra_content.txt';
        if (file_exists($filename)) {
            $value = file_get_contents($filename);
            return $value ;
        } else {
            return '';
        }
    }
    $value = null;
    if (is_null($key)) {
        $value = ((isset($_setting[$variable]) && $_setting[$variable] != '') ? $_setting[$variable] : null);         
    } else {
        if (isset($_setting[$variable][$key])) {
            $value = $_setting[$variable][$key];
        }
    }
    return $value;
}

/**
 * Returns an escaped version of $_SERVER['PHP_SELF'] to avoid XSS injection
 * @return string   Escaped version of $_SERVER['PHP_SELF']
 */
function api_get_self() {
    return htmlentities($_SERVER['PHP_SELF']);
}


/* USER PERMISSIONS */

/**
 * Checks whether current user is a platform administrator
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
 * Checks whether current user is allowed to create courses
 * @return boolean True if the user has course creation rights,
 * false otherwise.
 */
function api_is_allowed_to_create_course() {
    return $_SESSION['is_allowedCreateCourse'];
}

/**
 * Checks whether the current user is a course administrator
 * @return boolean True if current user is a course administrator
 */
function api_is_course_admin() {
    return $_SESSION['is_courseAdmin'];
}

/**
 * Checks whether the current user is a course coach
 * @return bool     True if current user is a course coach
 */
function api_is_course_coach() {
    return $_SESSION['is_courseCoach'];
}

/**
 * Checks whether the current user is a course tutor
 * @return bool     True if current user is a course tutor
 */
function api_is_course_tutor() {
    return $_SESSION['is_courseTutor'];
}

function api_get_user_platform_status($user_id = false) {
	$status     = array();
    $user_id    = intval($user_id);
    if (empty($user_id)) {
    	$user_id    = api_get_user_id();
    }

	if (empty($user_id)) {
		return false;
	}	
	$group_id   = api_get_group_id();
	$course_id  = api_get_course_int_id();
	$course_code= api_get_course_id();
	$session_id = api_get_session_id();
	
	//Group (in course)
    if ($group_id && $course_id) {
        $group_status = array();
        require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
        $is_subscribed = GroupManager::is_subscribed($user_id, $group_id);
        if ($is_subscribed) {
            $group_status = array('id'=> $group_id , 'status' => 'student');
            $is_tutor = GroupManager::is_tutor_of_group($user_id, $group_id);
            if ($is_tutor) {
                $group_status['status'] = 'tutor';            
            } else {
                $group_status['status'] = 'student';            
            }
        }
        $status['group'] = $group_status;
    }	

	//Session	
	if ($session_id && $course_id) {	
        $session_status = array();
        $session_status = array('id' => $session_id, 'course_id' => $course_id);
        $session_user_status = SessionManager::get_user_status_in_session($user_id, $course_code, $session_id);
        switch ($session_user_status) {
            case 0:
                $session_status['status'] = 'student';                 
               break;
            case 2:
                $session_status['status'] = 'coach';
            break;
        }
        $is_general_coach = SessionManager::user_is_general_coach($user_id, $session_id);
        if ($is_general_coach) {
            $session_status['status'] = 'general_coach';
        } 
    	$status['session'] = $session_status;

	} elseif($course_id) {
	    //Course
	    $course_status = array();
	    if ($course_id) {
            $user_course_status = CourseManager::get_user_in_course_status($user_id, $course_code);

            if ($user_course_status) {
                $course_status = array('id'=> $course_id);
                switch($user_course_status) {
                    case 1;
                        $course_status['status'] = 'teacher';
                    break;
                    case 5;
                        $course_status['status'] = 'student';
                        //check if tutor
                        $tutor_course_status = CourseManager::get_tutor_in_course_status($user_id, $course_code);
                        if ($tutor_course_status) {
                            $course_status['status'] = 'tutor';
                        }
                    break;
                } 
            }
	    }
	    $status['course'] = $course_status;
    }

    return $status;
}

/**
 * Checks whether the current user is a course or session coach
 * @param int - optional, session id
 * @param string - optional, course code
 * @return boolean True if current user is a course or session coach
 */
function api_is_coach($session_id = 0, $course_code = '') {
	//@todo Not sure about this one
	/*if (api_is_platform_admin()) {
		return true;
	}*/
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
    $session_table 						= Database::get_main_table(TABLE_MAIN_SESSION);
    $session_rel_course_rel_user_table  = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $sessionIsCoach = null;
	if (!empty($course_code)) {		
	    $sql = "SELECT DISTINCT id, name, date_start, date_end
				FROM $session_table INNER JOIN $session_rel_course_rel_user_table session_rc_ru
	            ON session_rc_ru.id_user = '".api_get_user_id()."'
	            WHERE session_rc_ru.course_code = '$course_code' AND session_rc_ru.status = 2 AND session_rc_ru.id_session = '$session_id'";		
	    $result = Database::query($sql);
	    
	    $sessionIsCoach = Database::store_result($result);	    
	}
	
	if (!empty($session_id)) {
	    $sql = "SELECT DISTINCT id, name, date_start, date_end
	         	FROM $session_table
	         	WHERE session.id_coach =  '".api_get_user_id()."' AND id = '$session_id'
				ORDER BY date_start, date_end, name";	
	    $result = Database::query($sql);	    
	    if (!empty($sessionIsCoach)) {
	    	$sessionIsCoach = array_merge($sessionIsCoach , Database::store_result($result));
	    } else {
	    	$sessionIsCoach = Database::store_result($result);	    	
	    }
	}
    return (count($sessionIsCoach) > 0);
}

/**
 * Checks whether the current user is a session administrator
 * @return boolean True if current user is a course administrator
 */
function api_is_session_admin() {
    global $_user;
    return $_user['status'] == SESSIONADMIN;
}

/**
 * Checks whether the current user is a human resources manager
 * @return boolean True if current user is a human resources manager
 */
function api_is_drh() {
    global $_user;
    return $_user['status'] == DRH;
}

/**
 * Checks whether the current user is a student
 * @return boolean True if current user is a human resources manager
 */
function api_is_student() {
    global $_user;
    return $_user['status'] == STUDENT;
		
}
/**
 * Checks whether the current user is a teacher
 * @return boolean True if current user is a human resources manager
 */
function api_is_teacher() {
    global $_user;
    return $_user['status'] == COURSEMANAGER;
}

/**
 * This function checks whether a session is assigned into a category
 * @param int       - session id
 * @param string    - category name
 * @return bool     - true if is found, otherwise false
 */
function api_is_session_in_category($session_id, $category_name) {

    $session_id = intval($session_id);
    $category_name = Database::escape_string($category_name);

    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $tbl_session_category = Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY);

    $sql = "select 1 FROM $tbl_session WHERE $session_id IN (SELECT s.id FROM $tbl_session s, $tbl_session_category sc  WHERE s.session_category_id = sc.id AND sc.name LIKE '%$category_name' )";
    $rs = Database::query($sql);

    if (Database::num_rows($rs) > 0) {
        return true;
    } else {
        return false;
    }
}

/* DISPLAY OPTIONS
   student view, title, message boxes,... */

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
    echo '<h2>';
    if (!empty($title_element['supraTitle'])) {
        echo '<small>'.$title_element['supraTitle'].'</small><br />';
    }
    if (!empty($title_element['mainTitle'])) {
        echo $title_element['mainTitle'];
    }
    if (!empty($title_element['subTitle'])) {
        echo '<br /><small>'.$title_element['subTitle'].'</small>';
    }
    echo '</h2>';
}

/**
 * Displays options for switching between student view and course manager view
 *
 * Changes in version 1.2 (Patrick Cool)
 * Student view switch now behaves as a real switch. It maintains its current state until the state
 * is changed explicitly
 *
 * Changes in version 1.1 (Patrick Cool)
 * student view now works correctly in subfolders of the document tool
 * student view works correctly in the new links tool
 *
 * Example code for using this in your tools:
 * //if ($is_courseAdmin && api_get_setting('student_view_enabled') == 'true') {
 * //   display_tool_view_option($isStudentView);
 * //}
 * //and in later sections, use api_is_allowed_to_edit()
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @version 1.2
 * @todo rewrite code so it is easier to understand
 */
function api_display_tool_view_option() {

    if (api_get_setting('student_view_enabled') != 'true') { 
        return '';
    }

    $output_string = '';

    $sourceurl = '';
    $is_framed = false;
    // Exceptions apply for all multi-frames pages
    if (strpos($_SERVER['REQUEST_URI'], 'chat/chat_banner.php') !== false) { // The chat is a multiframe bit that doesn't work too well with the student_view, so do not show the link
        $is_framed = true;
        return '';
    }

    /*// Uncomment to remove student view link from document view page
    if (strpos($_SERVER['REQUEST_URI'], 'document/headerpage.php') !== false) {
        $sourceurl = str_replace('document/headerpage.php', 'document/showinframes.php', $_SERVER['REQUEST_URI']);
        //showinframes doesn't handle student view anyway...
        //return '';
        $is_framed = true;
    }*/

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

    // Check whether the $_SERVER['REQUEST_URI'] contains already url parameters (thus a questionmark)
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
            // We have to remove the isStudentView=true from the $sourceurl
            $sourceurl = str_replace('&isStudentView=true', '', $sourceurl);
            $sourceurl = str_replace('&isStudentView=false', '', $sourceurl);
            $output_string .= '<a href="'.$sourceurl.'&isStudentView=false" target="_self">'.get_lang('CourseManagerview').'</a>';
        } elseif ($_SESSION['studentview'] == 'teacherview') {
            // Switching to teacherview
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
    if(is_array($info_array)) {
        foreach ($info_array as $element) {
            $message .= $element.'<br />';
        }
    }
    Display :: display_normal_message($message);
}

/**
 * Displays debug info
 * @param string $debug_info The message to display
 * @author Roan Embrechts
 * @version 1.1, March 2004
 */
function api_display_debug_info($debug_info) {
    $message = '<i>Debug info</i><br />';
    $message .= $debug_info;
    Display :: display_normal_message($message);
}

// TODO: This is for the permission section.
/**
 * Function that removes the need to directly use is_courseAdmin global in
 * tool scripts. It returns true or false depending on the user's rights in
 * this particular course.
 * Optionally checking for tutor and coach roles here allows us to use the
 * student_view feature altogether with these roles as well.
 * @param bool      Whether to check if the user has the tutor role
 * @param bool      Whether to check if the user has the coach role
 * @param bool      Whether to check if the user has the session coach role
 *
 * @author Roan Embrechts
 * @author Patrick Cool
 * @version 1.1, February 2004
 * @return boolean, true: the user has the rights to edit, false: he does not
 */

function api_is_allowed_to_edit($tutor = false, $coach = false, $session_coach = false) {

    $my_session_id 				= api_get_session_id();
    $is_allowed_coach_to_edit 	= api_is_coach();
    $session_visibility 		= api_get_session_visibility($my_session_id);
    
    $is_courseAdmin = api_is_course_admin() || api_is_platform_admin();
    
    if (!$is_courseAdmin && $tutor) {   // If we also want to check if the user is a tutor...
        $is_courseAdmin = $is_courseAdmin || api_is_course_tutor();
    }
    if (!$is_courseAdmin && $coach) {   // If we also want to check if the user is a coach...';
        // Check if session visibility is read only for coaches.
        if ($session_visibility == SESSION_VISIBLE_READ_ONLY) {
            $is_allowed_coach_to_edit = false;
        }

        if (api_get_setting('allow_coach_to_edit_course_session') == 'true') { // Check if coach is allowed to edit a course.
            $is_courseAdmin = $is_courseAdmin || $is_allowed_coach_to_edit;
        } else {
            $is_courseAdmin = $is_courseAdmin;
        }

    }
    if (!$is_courseAdmin && $session_coach) {
        $is_courseAdmin = $is_courseAdmin || api_is_coach();
    }

    if (api_get_setting('student_view_enabled') == 'true') { // Check if the student_view is enabled, and if so, if it is activated.
        if (!empty($my_session_id)) {
            // Check if session visibility is read only for coachs
            if ($session_visibility == SESSION_VISIBLE_READ_ONLY) {
                $is_allowed_coach_to_edit = false;
            }
            if (api_get_setting('allow_coach_to_edit_course_session') == 'true') { // Check if coach is allowed to edit a course.
                $is_allowed = $is_allowed_coach_to_edit;
            } else {
                $is_allowed = false;
            }
            $is_allowed = $is_allowed && $_SESSION['studentview'] != 'studentview';
        } else {
            $is_allowed = $is_courseAdmin && $_SESSION['studentview'] != 'studentview';
        }
        return $is_allowed;
    } else {
        return $is_courseAdmin;
    }
}

/**
* Checks if a student can edit contents in a session depending
* on the session visibility
* @param bool       Whether to check if the user has the tutor role
* @param bool       Whether to check if the user has the coach role
* @return boolean, true: the user has the rights to edit, false: he does not
*/
function api_is_allowed_to_session_edit($tutor = false, $coach = false) {
    if (api_is_allowed_to_edit($tutor, $coach)) {
        // If I'm a teacher, I will return true in order to not affect the normal behaviour of Chamilo tools.
        return true;
    } else {
        if (api_get_session_id() == 0) {
            // I'm not in a session so i will return true to not affect the normal behaviour of Chamilo tools.
            return true;
        } else {
            // I'm in a session and I'm a student
            $session_id = api_get_session_id();
            // Get the session visibility
            $session_visibility = api_get_session_visibility($session_id);  // if 0 the session is still available.
            if ($session_visibility != 0) {
                //@todo We could load the session_rel_course_rel_user permission to increase the level of detail.
                //echo api_get_user_id();
                //echo api_get_course_id();

                switch ($session_visibility) {
                    case SESSION_VISIBLE_READ_ONLY: // 1
                        return false;
                    case SESSION_VISIBLE:           // 2
                        return true;
                    case SESSION_INVISIBLE:         // 3
                        return false;
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
    //if (!$_SESSION['total_permissions'][$_course['code']] and $_course)
    if (is_array($_course) and count($_course) > 0) {
        require_once api_get_path(SYS_CODE_PATH).'permissions/permissions_functions.inc.php';
        require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

        // Getting the permissions of this user.
        if ($task_id == 0) {
            $user_permissions = get_permissions('user', $_user['user_id']);
            $_SESSION['total_permissions'][$_course['code']] = $user_permissions;
        }

        // Getting the permissions of the task.
        if ($task_id != 0) {
            $task_permissions = get_permissions('task', $task_id);
            /* !!! */$_SESSION['total_permissions'][$_course['code']] = $task_permissions;
        }
        //print_r($_SESSION['total_permissions']);

        // Getting the permissions of the groups of the user
        //$groups_of_user = GroupManager::get_group_ids($_course['db_name'], $_user['user_id']);

        //foreach($groups_of_user as $group)
        //   $this_group_permissions = get_permissions('group', $group);

        // Getting the permissions of the courseroles of the user
        $user_courserole_permissions = get_roles_permissions('user', $_user['user_id']);

        // Getting the permissions of the platformroles of the user
        //$user_platformrole_permissions = get_roles_permissions('user', $_user['user_id'], ', platform');

        // Getting the permissions of the roles of the groups of the user
        //foreach($groups_of_user as $group)
        //    $this_group_courserole_permissions = get_roles_permissions('group', $group);

        // Getting the permissions of the platformroles of the groups of the user
        //foreach($groups_of_user as $group)
        //    $this_group_platformrole_permissions = get_roles_permissions('group', $group, 'platform');
    }

    // If the permissions are limited, we have to map the extended ones to the limited ones.
    if (api_get_setting('permissions') == 'limited') {
        if ($action == 'Visibility') {
            $action = 'Edit';
        }
        if ($action == 'Move') {
            $action = 'Edit';
        }
    }

    // The session that contains all the permissions already exists for this course
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
 * @param int       User ID (optional, will take session ID if not provided)
 * @param bool      Whether to check in the database (true) or simply in the session (false) to see if the current user is the anonymous user
 * @return bool     true if this user is anonymous, false otherwise
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
        // In some cases, api_set_anonymous doesn't seem to be triggered in local.inc.php. Make sure it is.
        // Occurs in agenda for admin links - YW
        global $use_anonymous;
        if (isset($use_anonymous) && $use_anonymous) {
            api_set_anonymous();
        }
        return true;
    }
    return isset($_user['is_anonymous']) && $_user['is_anonymous'] === true;
}

/**
 * Displays message "You are not allowed here..." and exits the entire script.
 * @param bool      Whether or not to print headers (default = false -> does not print them)
 *
 * @author Roan Embrechts
 * @author Yannick Warnier
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @version 1.0, February 2004
 * @version dokeos 1.8, August 2006
 */
function api_not_allowed($print_headers = false) {
	
    $home_url   = api_get_path(WEB_PATH);
    $user       = api_get_user_id(); //0 if not defined
    $course     = api_get_course_id();

    global $this_section;

    if (api_get_setting('use_custom_pages') == 'true') {
        $_SESSION['request_uri'] = $_SERVER['REQUEST_URI'];
        require_once api_get_path(LIBRARY_PATH).'custompages.lib.php';
        CustomPages::displayPage('index-unlogged');
        exit;
    }
	
    $origin = isset($_GET['origin']) ? $_GET['origin'] : '';
    
    if ($origin == 'learnpath') {
	   $htmlHeadXtra[]= '<style type="text/css" media="screen, projection">
					/*<![CDATA[*/
	                @import "'.api_get_path(WEB_CODE_PATH).'css/'.api_get_setting('stylesheets').'/default.css";
	                /*]]>*/
	                </style>';
    }
    
    $msg = Display::return_message(get_lang('NotAllowedClickBack'), 'error', false);
    $msg = Display::div($msg, array('align'=>'center'));
	
    $show_headers = 0;
    if ((!headers_sent() || $print_headers) && $origin != 'learnpath') {
        $show_headers = 1;
    }
    $tpl = new Template(null, $show_headers, $show_headers);
    $tpl->assign('content', $msg);	
    if (($user!=0 && !api_is_anonymous()) && (!isset($course) || $course == -1) && empty($_GET['cidReq'])) {
        // if the access is not authorized and there is some login information
        // but the cidReq is not found, assume we are missing course data and send the user
        // to the user_portal		
        $tpl->display_one_col_template();
        exit;
    }

    if (!empty($_SERVER['REQUEST_URI']) && (!empty($_GET['cidReq']) || $this_section == SECTION_MYPROFILE)) {
        //only display form and return to the previous URL if there was a course ID included
        if ($user!=0 && !api_is_anonymous()) {
            //if there is a user ID, then the user is not allowed but the session is still there. Say so and exit
            $tpl->assign('content', $msg);
            $tpl->display_one_col_template();
            exit;
        }
        // If the user has no user ID, then his session has expired
        require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
        $form = new FormValidator('formLogin', 'post', api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']), null, array('class'=>'form-stacked'));
        $form->addElement('text', 'login', get_lang('UserName'), array('size' => 17));
        $form->addElement('password', 'password', get_lang('Password'), array('size' => 17));
        $form->addElement('style_submit_button', 'submitAuth', get_lang('LoginEnter'),'class="a_button gray"');

        $content = Display::return_message(get_lang('NotAllowed').'<br />'.get_lang('PleaseLoginAgainFromFormBelow').'<br />', 'error', false);

        $content .= '<div style="margin: 0 auto; width: 200px;" class="menu-wrapper"><div class="menu" id="menu">';
		
        $renderer =& $form->defaultRenderer();
        //$renderer->setElementTemplate('<div><label>{label}</label></div><div>{element}</div>');
        $renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>');
        $content .= $form->return_form();
        $content .='</div></div>';
        $tpl->assign('content', $content);
        $tpl->display_one_col_template();	
        exit;
    }

    if ($user!=0 && !api_is_anonymous()) {
        $tpl->display_one_col_template();
        exit;
    }    

    //if no course ID was included in the requested URL, then the user has either lost his session or is anonymous, so redirect to homepage
    $msg = Display::return_message(get_lang('NotAllowed').'<br /><br /><a href="'.$home_url.'">'.get_lang('PleaseLoginAgainFromHomepage').'</a><br />', 'error', false);
    $msg = Display::div($msg, array('align'=>'center'));
    $tpl->assign('content', $msg);	
    $tpl->display_one_col_template();
    exit;
}


/* WHAT'S NEW
   functions for the what's new icons
   in the user course list */

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
 *
 * Getting the visibility is done by getting the last updated visibility entry,
 * using the largest session ID found if session 0 and another was found (meaning
 * the only one that is actually from the session, in case there are results from
 * session 0 *AND* session n).
 * @param array     Course properties array (result of api_get_course_info())
 * @param string    Tool (learnpath, document, etc)
 * @param int       The item ID in the given tool
 * @param int       The session ID (optional)
 * @return int      -1 on error, 0 if invisible, 1 if visible
 */
function api_get_item_visibility($_course, $tool, $id, $session=0) {
    if (!is_array($_course) || count($_course) == 0 || empty($tool) || empty($id)) { return -1; }
    $tool = Database::escape_string($tool);
    $id = Database::escape_string($id);
    $session = (int) $session;
    $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $course_id	 = $_course['real_id'];
    $sql = "SELECT visibility FROM $TABLE_ITEMPROPERTY 
    		WHERE 	c_id = $course_id AND 
    				tool = '$tool' AND 
    				ref = $id AND 
    				(id_session = $session OR id_session = 0) 
    		ORDER BY id_session DESC, lastedit_date DESC";
    $res = Database::query($sql);
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
 *                                              (2) "delete"; (3) "visible"; (4) "invisible";
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
function api_item_property_update($_course, $tool, $item_id, $lastedit_type, $user_id, $to_group_id = 0, $to_user_id = null, $start_visible = 0, $end_visible = 0, $session_id = 0) {

    // Definition of variables.
    $tool           = Database::escape_string($tool);
    $item_id        = Database::escape_string($item_id);
    $lastedit_type  = Database::escape_string($lastedit_type);
    $user_id        = Database::escape_string($user_id);
    $to_group_id    = Database::escape_string($to_group_id);
    $to_user_id     = Database::escape_string($to_user_id);
    $start_visible  = Database::escape_string($start_visible);
    $end_visible    = Database::escape_string($end_visible);
    $start_visible  = ($start_visible == 0) ? '0000-00-00 00:00:00' : $start_visible;
    $end_visible    = ($end_visible == 0) ? '0000-00-00 00:00:00' : $end_visible;
    $to_filter      = '';
    $time           = api_get_utc_datetime();

    if (!empty($session_id)) {
        $session_id = intval($session_id);
    } else {
         $session_id = api_get_session_id();
    }

    // Definition of tables.
    $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

    if ($to_user_id <= 0) {
        $to_user_id = null; // No to_user_id set
    }

    if (!is_null($to_user_id)) {
        // $to_user_id has more priority than $to_group_id
        $to_field = 'to_user_id';
        $to_value = $to_user_id;
    } else {
        // $to_user_id is not set.
        $to_field = 'to_group_id';
        $to_value = $to_group_id;
    }

    // Set filters for $to_user_id and $to_group_id, with priority for $to_user_id

    $condition_session = '';
    if (!empty($session_id)) {
        $condition_session = " AND id_session = '$session_id' ";
    }
    
    $course_id	 = $_course['real_id'];
    $filter = "c_id = $course_id AND tool='$tool' AND ref='$item_id' $condition_session ";

    if ($item_id == '*') {
        $filter = "c_id = $course_id  AND tool='$tool' AND visibility<>'2' $condition_session"; // For all (not deleted) items of the tool
    }
    // Check whether $to_user_id and $to_group_id are passed in the function call.
    // If both are not passed (both are null) then it is a message for everybody and $to_group_id should be 0 !
    if (is_null($to_user_id) && is_null($to_group_id)) {
        $to_group_id = 0;
    }
    if (!is_null($to_user_id)) {
        $to_filter = " AND to_user_id='$to_user_id' $condition_session"; // Set filter to intended user.
    } else {
        if (($to_group_id != 0) && $to_group_id == strval(intval($to_group_id))) {
            $to_filter = " AND to_group_id='$to_group_id' $condition_session"; // Set filter to intended group.
        }
    }

    // Update if possible
    $set_type = '';
    
    
    switch ($lastedit_type) {
        case 'delete' : // delete = make item only visible for the platform admin.
            $visibility = '2';
            if (!empty($session_id)) {
                // Check whether session id already exist into itemp_properties for updating visibility or add it.
                $sql = "SELECT id_session FROM $TABLE_ITEMPROPERTY 
                		WHERE c_id = $course_id AND tool = '$tool' AND ref='$item_id' AND id_session = '$session_id'";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $sql = "UPDATE $TABLE_ITEMPROPERTY
                            SET lastedit_type		= '".str_replace('_', '', ucwords($tool))."Deleted', 
                            	lastedit_date		= '$time', 
                            	lastedit_user_id	= '$user_id', 
                            	visibility			= '$visibility', 
                            	id_session 			= '$session_id' $set_type
                            WHERE $filter";
                } else {
                    $sql = "INSERT INTO $TABLE_ITEMPROPERTY (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, id_session)
                            VALUES ($course_id, '$tool','$item_id','$time', '$user_id', '$time', '$lastedit_type','$user_id', '$to_value', '$visibility', '$start_visible','$end_visible', '$session_id')";
                }

            } else {
                $sql = "UPDATE $TABLE_ITEMPROPERTY SET lastedit_type='".str_replace('_', '', ucwords($tool))."Deleted', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
                        WHERE $filter";
            }
            break;
        case 'visible' : // Change item to visible.
            $visibility = '1';

            if (!empty($session_id)) {

                // Check whether session id already exist into itemp_properties for updating visibility or add it.
                $sql = "SELECT id_session FROM $TABLE_ITEMPROPERTY WHERE c_id=$course_id AND tool = '$tool' AND ref='$item_id' AND id_session = '$session_id'";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $sql = "UPDATE $TABLE_ITEMPROPERTY
                            SET lastedit_type='".str_replace('_', '', ucwords($tool))."Visible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility', id_session = '$session_id' $set_type
                            WHERE $filter";
                } else {
                    $sql = "INSERT INTO $TABLE_ITEMPROPERTY (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, id_session)
                            VALUES ($course_id, '$tool', '$item_id', '$time', '$user_id', '$time', '$lastedit_type', '$user_id', '$to_value', '$visibility', '$start_visible', '$end_visible', '$session_id')";
                }
            } else {
                $sql = "UPDATE $TABLE_ITEMPROPERTY
                        SET lastedit_type='".str_replace('_', '', ucwords($tool))."Visible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
                        WHERE $filter";
            }
            break;
        case 'invisible' : // Change item to invisible.
            $visibility = '0';

            if (!empty($session_id)) {

                // Check whether session id already exist into itemp_properties for updating visibility or add it
                $sql = "SELECT id_session FROM $TABLE_ITEMPROPERTY WHERE c_id=$course_id AND tool = '$tool' AND ref='$item_id' AND id_session = '$session_id'";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) > 0) {
                    $sql = "UPDATE $TABLE_ITEMPROPERTY
                            SET lastedit_type='".str_replace('_', '', ucwords($tool))."Invisible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility', id_session = '$session_id' $set_type
                            WHERE $filter";
                } else {
                    $sql = "INSERT INTO $TABLE_ITEMPROPERTY (c_id, tool, ref, insert_date, insert_user_id, lastedit_date, lastedit_type, lastedit_user_id,$to_field, visibility, start_visible, end_visible, id_session)
                            VALUES ($course_id, '$tool', '$item_id', '$time', '$user_id', '$time', '$lastedit_type', '$user_id', '$to_value', '$visibility', '$start_visible', '$end_visible', '$session_id')";
                }

            } else {
                $sql = "UPDATE $TABLE_ITEMPROPERTY
                        SET lastedit_type='".str_replace('_', '', ucwords($tool))."Invisible', lastedit_date='$time', lastedit_user_id='$user_id', visibility='$visibility' $set_type
                        WHERE $filter";
            }
            break;
        default : // The item will be added or updated.
            $set_type = ", lastedit_type='$lastedit_type' ";
            $visibility = '1';
            $filter .= $to_filter;
            $sql = "UPDATE $TABLE_ITEMPROPERTY
                    SET lastedit_date='$time', lastedit_user_id='$user_id' $set_type
                    WHERE $filter";
    }
    
    $res = Database::query($sql);
    // Insert if no entries are found (can only happen in case of $lastedit_type switch is 'default').
    if (Database::affected_rows() == 0) {
        $sql = "INSERT INTO $TABLE_ITEMPROPERTY (c_id, tool,ref,insert_date,insert_user_id,lastedit_date,lastedit_type,   lastedit_user_id,$to_field,  visibility,   start_visible,   end_visible, id_session)
                VALUES ($course_id, '$tool', '$item_id', '$time', '$user_id', '$time', '$lastedit_type', '$user_id', '$to_value', '$visibility', '$start_visible', '$end_visible', '$session_id')";
        
        $res = Database::query($sql);
        if (!$res) {
            return false;
        }
    }
    return true;
}

/**
 * Gets item property by tool
 * @param string    course code
 * @param string    tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int       id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 */
function api_get_item_property_by_tool($tool, $course_code, $session_id = null) {

    $course_info    = api_get_course_info($course_code);
    $tool           = Database::escape_string($tool);
    
    // Definition of tables.
    $item_property_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $session_condition = '';
    $session_id = intval($session_id);
    $session_condition = ' AND id_session = '.$session_id;
    $course_id	 = $course_info['real_id'];
    
    $sql = "SELECT * FROM $item_property_table WHERE c_id = $course_id AND tool = '$tool'  $session_condition ";
    $rs  = Database::query($sql);
    $item_property_id = '';
    $list = array();
    if (Database::num_rows($rs) > 0) {        
        while ($row = Database::fetch_array($rs, 'ASSOC')) {
            $list[] = $row;
        }        
    }
    return $list;
}

/**
 * Gets item property id from tool of a course
 * @param string    course code
 * @param string    tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int       id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 */
function api_get_item_property_id($course_code, $tool, $ref) {

    $course_info    = api_get_course_info($course_code);
    $tool           = Database::escape_string($tool);
    $ref            = intval($ref);  

    // Definition of tables.
    $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $course_id	 = $course_info['real_id'];
    $sql = "SELECT id FROM $TABLE_ITEMPROPERTY WHERE c_id = $course_id AND tool = '$tool' AND ref = '$ref'";
    $rs  = Database::query($sql);
    $item_property_id = '';
    if (Database::num_rows($rs) > 0) {
        $row = Database::fetch_array($rs);
        $item_property_id = $row['id'];
    }
    return $item_property_id;
}


/**
 *
 * Inserts a record in the track_e_item_property table (No update)
 * 
 */
   
function api_track_item_property_update($tool, $ref, $title, $content, $progress) {
        $tbl_stats_item_property = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
        $course_id = api_get_real_course_id(); //numeric
        $course_code = api_get_course_id(); //alphanumeric
        $item_property_id = api_get_item_property_id($course_code, $tool, $ref);
        if (!empty($item_property_id)) {
            $sql = "INSERT IGNORE INTO $tbl_stats_item_property SET
                    course_id           = '$course_id',
                    item_property_id    = '$item_property_id',
                    title               = '".Database::escape_string($title)."',
                    content             = '".Database::escape_string($content)."',
                    progress            = '".intval($progress)."',
                    lastedit_date       = '".api_get_utc_datetime()."',
                    lastedit_user_id    = '".api_get_user_id()."',
                    session_id          = '".api_get_session_id()."'";
            Database::query($sql);
            $affected_rows = Database::affected_rows();
            return $affected_rows;
        }
        return false;
}

function api_get_track_item_property_history($tool, $ref) {
    $tbl_stats_item_property = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ITEM_PROPERTY);
    $course_id = api_get_real_course_id(); //numeric
    $course_code = api_get_course_id(); //alphanumeric
    $item_property_id = api_get_item_property_id($course_code, $tool, $ref);
    $sql = "SELECT * FROM $tbl_stats_item_property WHERE item_property_id = $item_property_id AND course_id = $course_id ORDER BY lastedit_date DESC";
    $result = Database::query($sql);
    $result = Database::store_result($result,'ASSOC');
    return $result;
}

/**
 * Gets item property data from tool of a course id
 * @param int    	course id
 * @param string    tool name, linked to 'rubrique' of the course tool_list (Warning: language sensitive !!)
 * @param int       id of the item itself, linked to key of every tool ('id', ...), "*" = all items of the tool
 */
function api_get_item_property_info($course_id, $tool, $ref, $session_id = 0) {

    $course_info    = api_get_course_info_by_id($course_id);
    if (empty($course_info)) {
        return false;
    }
    
    $tool           = Database::escape_string($tool);
    $ref            = intval($ref);
    $course_id      = intval($course_id);
    
    // Definition of tables.
    $TABLE_ITEMPROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $course_id	 = $course_info['real_id'];
    
   	$sql = "SELECT * FROM $TABLE_ITEMPROPERTY WHERE c_id = $course_id AND tool = '$tool' AND ref = $ref ";
   	if (!empty($session_id)) {
   		$session_id = intval($session_id);
   		$sql .= "AND id_session = $session_id ";
   	}
   	
    $rs  = Database::query($sql);    
    $row = array();
    if (Database::num_rows($rs) > 0) {
        $row = Database::fetch_array($rs,'ASSOC');   
    }
    return $row;
}


/* Language Dropdown */

/**
 * Displays a combobox so the user can select his/her preferred language.
 * @param string The desired name= value for the select
 * @return string
 */

function api_get_languages_combo($name = 'language') {
    $ret = '';
    $platformLanguage = api_get_setting('platformLanguage');

    // Retrieve a complete list of all the languages.
    $language_list = api_get_languages();

    if (count($language_list['name']) < 2) {
        return $ret;
    }

    // The the current language of the user so that his/her language occurs as selected in the dropdown menu.
    if (isset($_SESSION['user_language_choice'])) {
        $default = $_SESSION['user_language_choice'];
    } else {
        $default = $platformLanguage;
    }

    $languages  = $language_list['name'];
    $folder		= $language_list['folder'];

    $ret .= '<select name="'.$name.'" id="language_chosen" class="chzn-select" >';
    foreach ($languages as $key => $value) {
        if ($folder[$key] == $default) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        $ret .= sprintf('<option value=%s" %s>%s</option>', $folder[$key], $selected, $value);
    }
    $ret .= '</select>';
    return $ret;
}

/**
 * Displays a form (drop down menu) so the user can select his/her preferred language.
 * The form works with or without javascript
 * @param  boolean Hide form if only one language available (defaults to false = show the box anyway)
 * @return void Display the box directly
 */
function api_display_language_form($hide_if_no_choice = false) {

    // Retrieve a complete list of all the languages.
    $language_list = api_get_languages();
    if (count($language_list['name']) <= 1 && $hide_if_no_choice) {
        return; //don't show any form
    }

    // The the current language of the user so that his/her language occurs as selected in the dropdown menu.
    if (isset($_SESSION['user_language_choice'])) {
        $user_selected_language = $_SESSION['user_language_choice'];
    }
    if (empty($user_selected_language)) {
        $user_selected_language = api_get_setting('platformLanguage');
    }

    $original_languages = $language_list['name'];
    $folder = $language_list['folder']; // This line is probably no longer needed.
	$html = '
    <script type="text/javascript">
    <!--
    function jumpMenu(targ,selObj,restore){ // v3.0
        eval(targ+".location=\'"+selObj.options[selObj.selectedIndex].value+"\'");
        if (restore) selObj.selectedIndex=0;
    }
    //-->
    </script>';

    $html .= '<form id="lang_form" name="lang_form" method="post" action="'.api_get_self().'">';
    $html .=  '<select id="language_list" class="chzn-select" name="language_list" onchange="javascript: jumpMenu(\'parent\',this,0);">';
    foreach ($original_languages as $key => $value) {
        if ($folder[$key] == $user_selected_language) {
            $option_end = ' selected="selected" >';
        } else {
            $option_end = '>';
        }
        $html .=  '<option value="'.api_get_self().'?language='.$folder[$key].'"'.$option_end;
        //echo substr($value, 0, 16); // Cut string to keep 800x600 aspect.
        $html .=  $value.'</option>';
    }
    $html .=  '</select>';
    $html .=  '<noscript><input type="submit" name="user_select_language" value="'.get_lang('Ok').'" /></noscript>';
    $html .=  '</form>';
    return $html;
}

/**
 * Returns a list of all the languages that are made available by the admin.
 * @return array An array with all languages. Structure of the array is
 *  array['name'] = An array with the name of every language
 *  array['folder'] = An array with the corresponding names of the language-folders in the filesystem
 */
function api_get_languages() {
    $tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    $sql = "SELECT * FROM $tbl_language WHERE available='1' ORDER BY original_name ASC";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $language_list['name'][] = $row['original_name'];
        $language_list['folder'][] = $row['dokeos_folder'];
    }
    return $language_list;
}

/**
 * Returns the id (the database id) of a language
 * @param   string  language name (the corresponding name of the language-folder in the filesystem)
 * @return  int     id of the language
 */
function api_get_language_id($language) {
    $tbl_language = Database::get_main_table(TABLE_MAIN_LANGUAGE);
    if (empty($language)) {
        return null;
    }
    $language = Database::escape_string($language);
    $sql = "SELECT id FROM $tbl_language WHERE available='1' AND dokeos_folder = '$language' LIMIT 1";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);    
    return $row['id'];
}
/**
 * Gets language of the requested type for the current user. Types are : 
 * user_profil_lang : profile language of current user
 * user_select_lang : language selected by user at login
 * course_lang : language of the current course
 * platform_lang : default platform language
 * @param string lang_type
 * @param return language of the requested type or false if the language is not available
 **/
function api_get_language_from_type($lang_type){
  global $_user;
  global $_course;
  $toreturn = false;
  switch ($lang_type) {
  case 'platform_lang' : 
    $temp_lang = api_get_setting('platformLanguage');
    if (!empty($temp_lang))
      $toreturn = $temp_lang;
    break;
  case 'user_profil_lang' : 
    if (isset($_user['language']) && !empty($_user['language']) ) 
      $toreturn = $_user['language'];
    break;
  case 'user_selected_lang' : 
    if (isset($_SESSION['user_language_choice']) && !empty($_SESSION['user_language_choice']) ) 
      $toreturn = ($_SESSION['user_language_choice']);
    break;
  case 'course_lang' : 
    if ($_course['language'] && !empty($_course['language']) )  
      $toreturn = $_course['language'];
    break;
  default : 
    $toreturn = false;
    break;
  }
  return $toreturn;
}

function api_get_language_info($language_id) {
    $tbl_admin_languages = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
    $sql = 'SELECT * FROM '.$tbl_admin_languages.' WHERE id = "'.intval($language_id).'"';
    $rs = Database::query($sql);
    $language_info = array();
    if (Database::num_rows($rs)) {
        $language_info = Database::fetch_array($rs,'ASSOC');
    }     
    return $language_info;    
}

/**
 * Returns the name of the visual (CSS) theme to be applied on the current page.
 * The returned name depends on the platform, course or user -wide settings.
 * @return string   The visual theme's name, it is the name of a folder inside .../chamilo/main/css/
 */
function api_get_visual_theme() {

    static $visual_theme;

    if (!isset($visual_theme)) {

        $platform_theme = api_get_setting('stylesheets');   // Plataform's theme.
        $visual_theme = $platform_theme;

        if (api_get_setting('user_selected_theme') == 'true') {
            $user_info = api_get_user_info();
            $user_theme = $user_info['theme'];
            if (!empty($user_theme)) {
                $visual_theme = $user_theme;                // User's theme.
            }
        }

        $course_id = api_get_course_id();
        if (!empty($course_id) && $course_id != -1) {
            if (api_get_setting('allow_course_theme') == 'true') {
                $course_theme = api_get_course_setting('course_theme');

                if (!empty($course_theme) && $course_theme != -1) {
                    if (!empty($course_theme)) {
                        $visual_theme = $course_theme;      // Course's theme.
                    }
                }

                $allow_lp_theme = api_get_course_setting('allow_learning_path_theme');
                if ($allow_lp_theme == 1) {
                    global $lp_theme_css, $lp_theme_config; // These variables come from the file lp_controller.php.
                    if (!$lp_theme_config) {
                        if (!empty($lp_theme_css)) {
                            $visual_theme = $lp_theme_css;  // LP's theme.
                        }
                    }
                }
            }
        }

        if (empty($visual_theme)) {
            $visual_theme = 'chamilo';
        }

        global $lp_theme_log;
        if ($lp_theme_log) {
            $visual_theme = $platform_theme;
        }
    }

    return $visual_theme;
}

/**
 * Returns a list of CSS themes currently available in the CSS folder
 * @return array        List of themes directories from the css folder
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

                foreach ($themes as & $theme) {
                    if (substr($theme, 0, 1) == '.') {
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
    return array($list_dir, $list_name);
}



/* WYSIWYG EDITOR
   Functions for the WYSIWYG html editor.
   Please, try to avoid using the following two functions. The preferable way to put
   an editor's instance on a page is through using a FormValidator's class method. */

/**
 * Displays the WYSIWYG editor for online editing of html
 * @param string $name The name of the form-element
 * @param string $content The default content of the html-editor
 * @param int $height The height of the form element
 * @param int $width The width of the form element
 * @param string $attributes (optional) attributes for the form element
 * @param array $editor_config (optional) Configuration options for the html-editor
 */
function api_disp_html_area($name, $content = '', $height = '', $width = '100%', $attributes = null, $editor_config = null) {
    global $_configuration, $_course, $fck_attribute;
    require_once api_get_path(LIBRARY_PATH).'formvalidator/Element/html_editor.php';
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

/**
 * Returns generated html for showing the WYSIWYG editor on the page
 * @param string $name The name of the form-element
 * @param string $content The default content of the html-editor
 * @param int $height The height of the form element
 * @param int $width The width of the form element
 * @param string $attributes (optional) attributes for the form element
 * @param array $editor_config (optional) Configuration options for the html-editor
 */
function api_return_html_area($name, $content = '', $height = '', $width = '100%', $attributes = null, $editor_config = null) {
    global $_configuration, $_course, $fck_attribute;
    require_once api_get_path(LIBRARY_PATH).'formvalidator/Element/html_editor.php';
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
 * Find the largest sort value in a given user_course_category
 * This function is used when we are moving a course to a different category
 * and also when a user subscribes to courses (the new course is added at the end of the main category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $user_course_category: the id of the user_course_category
 * @return int the value of the highest sort of the user_course_category
 */
function api_max_sort_value($user_course_category, $user_id) {
    $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

    $sql_max = "SELECT max(sort) as max_sort FROM $tbl_course_user WHERE user_id='".intval($user_id)."' AND relation_type<>".COURSE_RELATION_TYPE_RRHH." AND user_course_cat='".Database::escape_string($user_course_category)."'";
    $result_max = Database::query($sql_max);
    if (Database::num_rows($result_max) == 1) {
        $row_max = Database::fetch_array($result_max);
        return $row_max['max_sort'];
    }
    return 0;
}

/**
 * This function converts the string "true" or "false" to a boolean true or false.
 * This function is in the first place written for the Chamilo Config Settings (also named AWACS)
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
    return false;
}

/**
 * Determines the number of plugins installed for a given location
 */
function api_number_of_plugins($location) {
    global $_plugins;
    return isset($_plugins[$location]) && is_array($_plugins[$location]) ? count($_plugins[$location]) : 0;
}

/**
 * Including the necessary plugins.
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
    if(is_array($plugin_list)) {
        foreach ($plugin_list as $plugin_location) {
            if (array_search($plugin_name, $plugin_location) !== false) { return true; }
        }
    }
    return false;
}

/**
 * Transforms a number of seconds in hh:mm:ss format
 * @author Julian Prud'homme
 * @param integer the number of seconds
 * @return string the formated time
 */
function api_time_to_hms($seconds) {

    // $seconds = -1 means that we have wrong data in the db.
    if ($seconds == -1) {
        return get_lang('Unknown').Display::return_icon('info2.gif', get_lang('WrongDatasForTimeSpentOnThePlatform'), array('align' => 'absmiddle', 'hspace' => '3px'));
    }

    // How many hours ?
    $hours = floor($seconds / 3600);

    // How many minutes ?
    $min = floor(($seconds - ($hours * 3600)) / 60);

    // How many seconds
    $sec = floor($seconds - ($hours * 3600) - ($min * 60));

    if ($sec < 10) {
        $sec = "0$sec";
    }

    if ($min < 10) {
        $min = "0$min";
    }

    return "$hours:$min:$sec";
}


/* FILE SYSTEM RELATED FUNCTIONS */

/**
 * Returns the permissions to be assigned to every newly created directory by the web-server.
 * The returnd value is based on the platform administrator's setting "Administration > Configuration settings > Security > Permissions for new directories".
 * @return int      Returns the permissions in the format "Owner-Group-Others, Read-Write-Execute", as an integer value.
 */
function api_get_permissions_for_new_directories() {
    static $permissions;
    if (!isset($permissions)) {
        $permissions = trim(api_get_setting('permissions_for_new_directories'));
        // The default value 0777 is according to that in the platform administration panel after fresh system installation.
        $permissions = octdec(!empty($permissions) ? $permissions : '0777');
    }
    return $permissions;
}

/**
 * Returns the permissions to be assigned to every newly created directory by the web-server.
 * The returnd value is based on the platform administrator's setting "Administration > Configuration settings > Security > Permissions for new files".
 * @return int      Returns the permissions in the format "Owner-Group-Others, Read-Write-Execute", as an integer value.
 */
function api_get_permissions_for_new_files() {
    static $permissions;
    if (!isset($permissions)) {
        $permissions = trim(api_get_setting('permissions_for_new_files'));
        // The default value 0666 is according to that in the platform administration panel after fresh system installation.
        $permissions = octdec(!empty($permissions) ? $permissions : '0666');
    }
    return $permissions;
}

/**
 * sys_get_temp_dir() was introduced as of PHP 5.2.1
 * For older PHP versions the following implementation is to be activated.
 * @link Based on http://www.phpit.net/article/creating-zip-tar-archives-dynamically-php/2/
 */
if (!function_exists('sys_get_temp_dir')) {

    function sys_get_temp_dir() {

        // Try to get from environment variable.
        if (!empty($_ENV['TMP'])) {
            return realpath($_ENV['TMP']);
        }
        if (!empty($_ENV['TMPDIR'])) {
            return realpath($_ENV['TMPDIR']);
        }
        if (!empty($_ENV['TEMP'])) {
            return realpath($_ENV['TEMP']);
        }

        // Detect by creating a temporary file.
        // Try to use system's temporary directory
        // as random name shouldn't exist.
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
 * Deletes a file, or a folder and its contents
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.3
 * @param       string   $dirname    Directory to delete
 * @param       bool	 Deletes only the content or not
 * @return      bool     Returns TRUE on success, FALSE on failure
 * @link http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/
 * @author      Yannick Warnier, adaptation for the Chamilo LMS, April, 2008
 * @author      Ivan Tcholakov, a sanity check about Directory class creation has been added, September, 2009
 */
function rmdirr($dirname, $delete_only_content_in_folder = false) {
	$res = true;
	
    // A sanity check.
    if (!file_exists($dirname)) {
        return false;
    }
    $php_errormsg = '';
    // Simple delete for a file.
    if (is_file($dirname) || is_link($dirname)) {
        $res = unlink($dirname);
        if ($res === false) {
            error_log(__FILE__.' line '.__LINE__.': '.((bool)ini_get('track_errors') ? $php_errormsg : 'Error not recorded because track_errors is off in your php.ini'), 0);
        }
        return $res;
    }

    // Loop through the folder.
    $dir = dir($dirname);
    // A sanity check.
    $is_object_dir = is_object($dir);
    if ($is_object_dir) {
        while (false !== $entry = $dir->read()) {
            // Skip pointers.
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Recurse.
            rmdirr("$dirname/$entry");
        }
    }

    // Clean up.
    if ($is_object_dir) {
        $dir->close();
    }
    
    if ($delete_only_content_in_folder == false) {
	    $res = rmdir($dirname);
	    if ($res === false) {
	        error_log(__FILE__.' line '.__LINE__.': '.((bool)ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
	    }
    }
    return $res;
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
    if (empty($dest)) { return false; }
    // Simple copy for a file
    if (is_file($source)) {
        $path_info = pathinfo($source);
        if (!in_array($path_info['filename'], $exclude)) {
            copy($source, $dest);
        }
        return true;
    } elseif (!is_dir($source)) {
    	//then source is not a dir nor a file, return
        return false;
    }

    // Make destination directory.
    if (!is_dir($dest)) {
        mkdir($dest, api_get_permissions_for_new_directories());
    }

    // Loop through the folder.
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories.
        if ($dest !== "$source/$entry") {
            $files = copyr("$source/$entry", "$dest/$entry", $exclude, $copied_files);
        }
    }
    // Clean up.
    $dir->close();
    return true;
}

// TODO: Using DIRECTORY_SEPARATOR is not recommended, this is an obsolete approach. Documentation header to be added here.
function copy_folder_course_session($pathname, $base_path_document, $session_id, $course_info, $document) {
    $table = Database :: get_course_table(TABLE_DOCUMENT);
    $session_id = intval($session_id);
    // Check whether directory already exists.
    if (is_dir($pathname) || empty($pathname)) {
        return true;
    }

    // Ensure that a file with the same name does not already exist.
    if (is_file($pathname)) {
        trigger_error('copy_folder_course_session(): File exists', E_USER_WARNING);
        return false;
    }
    
    $course_id	 = $course_info['real_id'];

    $folders = explode(DIRECTORY_SEPARATOR,str_replace($base_path_document.DIRECTORY_SEPARATOR,'',$pathname));

    $new_pathname = $base_path_document;
    $path = '';

    foreach ($folders as $folder) {
        $new_pathname .= DIRECTORY_SEPARATOR.$folder;
        $path .= DIRECTORY_SEPARATOR.$folder;

        if (!file_exists($new_pathname)) {
            $path = Database::escape_string($path);

            $sql = "SELECT * FROM $table WHERE path = '$path' AND filetype = 'folder' AND session_id = '$session_id'";
            $rs1  = Database::query($sql);
            $num_rows = Database::num_rows($rs1);

            if ($num_rows == 0) {
                mkdir($new_pathname, api_get_permissions_for_new_directories());

                // Insert new folder with destination session_id.
                $sql = "INSERT INTO ".$table." SET
                		c_id = $course_id,  
                		path = '$path',                 
                		comment = '".Database::escape_string($document->comment)."', 
                		title = '".Database::escape_string(basename($new_pathname))."' ,
                		filetype='folder', 
                		size= '0', 
                		session_id = '$session_id'";
                Database::query($sql);
                $document_id = Database::insert_id();
                api_item_property_update($course_info,TOOL_DOCUMENT,$document_id,'FolderCreated',api_get_user_id(),0,0,null,null,$session_id);
            }
        }

    } // en foreach
}

// TODO: chmodr() is a better name. Some corrections are needed. Documentation header to be added here.
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


// TODO: Where the following function has been copy/pased from? There is no information about author and license. Style, coding conventions...
/**
 * Parse info file format. (e.g: file.info)
 *
 * Files should use an ini-like format to specify values.
 * White-space generally doesn't matter, except inside values.
 * e.g.
 *
 * @verbatim
 *   key = value
 *   key = "value"
 *   key = 'value'
 *   key = "multi-line
 *
 *   value"
 *   key = 'multi-line
 *
 *   value'
 *   key
 *   =
 *   'value'
 * @endverbatim
 *
 * Arrays are created using a GET-like syntax:
 *
 * @verbatim
 *   key[] = "numeric array"
 *   key[index] = "associative array"
 *   key[index][] = "nested numeric array"
 *   key[index][index] = "nested associative array"
 * @endverbatim
 *
 * PHP constants are substituted in, but only when used as the entire value:
 *
 * Comments should start with a semi-colon at the beginning of a line.
 *
 * This function is NOT for placing arbitrary module-specific settings. Use
 * variable_get() and variable_set() for that.
 *
 * Information stored in the module.info file:
 * - name: The real name of the module for display purposes.
 * - description: A brief description of the module.
 * - dependencies: An array of shortnames of other modules this module depends on.
 * - package: The name of the package of modules this module belongs to.
 *
 * Example of .info file:
 * <code>
 * @verbatim
 *   name = Forum
 *   description = Enables threaded discussions about general topics.
 *   dependencies[] = taxonomy
 *   dependencies[] = comment
 *   package = Core - optional
 *   version = VERSION
 * @endverbatim
 * </code>
 * @param $filename
 *   The file we are parsing. Accepts file with relative or absolute path.
 * @return
 *   The info array.
 */
function parse_info_file($filename) {
    $info = array();

    if (!file_exists($filename)) {
        return $info;
    }

    $data = file_get_contents($filename);
    if (preg_match_all('
        @^\s*                           # Start at the beginning of a line, ignoring leading whitespace
        ((?:
          [^=;\[\]]|                    # Key names cannot contain equal signs, semi-colons or square brackets,
          \[[^\[\]]*\]                  # unless they are balanced and not nested
        )+?)
        \s*=\s*                         # Key/value pairs are separated by equal signs (ignoring white-space)
        (?:
          ("(?:[^"]|(?<=\\\\)")*")|     # Double-quoted string, which may contain slash-escaped quotes/slashes
          (\'(?:[^\']|(?<=\\\\)\')*\')| # Single-quoted string, which may contain slash-escaped quotes/slashes
          ([^\r\n]*?)                   # Non-quoted string
        )\s*$                           # Stop at the next end of a line, ignoring trailing whitespace
        @msx', $data, $matches, PREG_SET_ORDER)) {
        $key = $value1 = $value2 = $value3 = '';
        foreach ($matches as $match) {
            // Fetch the key and value string.
            $i = 0;
            foreach (array('key', 'value1', 'value2', 'value3') as $var) {
                $$var = isset($match[++$i]) ? $match[$i] : '';
            }
            $value = stripslashes(substr($value1, 1, -1)) . stripslashes(substr($value2, 1, -1)) . $value3;

            // Parse array syntax.
            $keys = preg_split('/\]?\[/', rtrim($key, ']'));
            $last = array_pop($keys);
            $parent = &$info;

            // Create nested arrays.
            foreach ($keys as $key) {
                if ($key == '') {
                    $key = count($parent);
                }
                if (!isset($parent[$key]) || !is_array($parent[$key])) {
                    $parent[$key] = array();
                }
                $parent = &$parent[$key];
            }

            // Handle PHP constants.
            if (defined($value)) {
                $value = constant($value);
            }

            // Insert actual value.
            if ($last == '') {
                $last = count($parent);
            }
            $parent[$last] = $value;
        }
    }
    return $info;
}


/**
 * Gets Chamilo version from the configuration files
 * @return string   A string of type "1.8.4", or an empty string if the version could not be found
 */
function api_get_version() {
    global $_configuration;
    return (string)$_configuration['system_version'];
}

/**
 * Gets the software name (the name/brand of the Chamilo-based customized system)
 * @return string
 */
function api_get_software_name() {
    global $_configuration;
    if (isset($_configuration['software_name']) && !empty($_configuration['software_name'])) {
        return $_configuration['software_name'];
    } else {
        return 'Chamilo';
    }
}

/**
 * Checks whether status given in parameter exists in the platform
 * @param mixed the status (can be either int either string)
 * @return true if the status exists, else returns false
 */
function api_status_exists($status_asked) {
    global $_status_list;
    return in_array($status_asked, $_status_list) ? true : isset($_status_list[$status_asked]);
}

/**
 * Checks whether status given in parameter exists in the platform. The function
 * returns the status ID or false if it does not exist, but given the fact there
 * is no "0" status, the return value can be checked against
 * if(api_status_key()) to know if it exists.
 * @param   mixed   The status (can be either int or string)
 * @return  mixed   Status ID if exists, false otherwise
 */
function api_status_key($status) {
    global $_status_list;
    return isset($_status_list[$status]) ? $status : array_search($status, $_status_list);
}

/**
 * Gets the status langvars list
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
* The function that retrieves all the possible settings for a certain config setting
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function api_get_settings_options($var) {
	$table_settings_options = Database :: get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
	$sql = "SELECT * FROM $table_settings_options WHERE variable = '$var' ORDER BY id";
	$result = Database::query($sql);
    $settings_options_array = array();
	while ($row = Database::fetch_array($result, 'ASSOC')) {
		//$temp_array = array ('value' => $row['value'], 'display_text' => $row['display_text']);
		$settings_options_array[] = $row;
	}
	return $settings_options_array;
}

function api_set_setting_option($params) {
	$table = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
	if (empty($params['id'])) {
		Database::insert($table, $params);
	} else {
		Database::update($table, $params, array('id = ? '=> $params['id']));
	}		
}

function api_delete_setting_option($id) {
	$table = Database::get_main_table(TABLE_MAIN_SETTINGS_OPTIONS);
	if (!empty($id)) {
		Database::delete($table, array('id = ? '=> $id));
	}	
}

/**
 * Sets a platform configuration setting to a given value
 * @param string    The variable we want to update
 * @param string    The value we want to record
 * @param string    The sub-variable if any (in most cases, this will remain null)
 * @param string    The category if any (in most cases, this will remain null)
 * @param int       The access_url for which this parameter is valid
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

    $res = Database::query($select);
    if (Database::num_rows($res) > 0) {
        // Found item for this access_url.
        $row = Database::fetch_array($res);
        $update = "UPDATE $t_settings SET selected_value = '$value' WHERE id = ".$row['id'] ;
        $res = Database::query($update);
    } else {
        // Item not found for this access_url, we have to check if it exist with access_url = 1
        $select = "SELECT * FROM $t_settings WHERE variable = '$var' AND access_url = 1 ";
        // Just in case
        if ($access_url == 1) {
            if (!empty($subvar)) {
                $select .= " AND subkey = '$subvar'";
            }
            if (!empty($cat)) {
                $select .= " AND category = '$cat'";
            }
            $res = Database::query($select);

            if (Database::num_rows($res) > 0) { // We have a setting for access_url 1, but none for the current one, so create one.
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
                $res = Database::query($insert);
            } else { // Such a setting does not exist.
                error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all', 0);
            }
        } else {
            // Other access url.
            if (!empty($subvar)) {
                $select .= " AND subkey = '$subvar'";
            }
            if (!empty($cat)) {
                $select .= " AND category = '$cat'";
            }
            $res = Database::query($select);

            if (Database::num_rows($res) > 0) { // We have a setting for access_url 1, but none for the current one, so create one.
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
                    $res = Database::query($insert);
                }
            } else { // Such a setting does not exist.
                error_log(__FILE__.':'.__LINE__.': Attempting to update setting '.$var.' ('.$subvar.') which does not exist at all. The access_url is: '.$access_url.' ',0);
            }
        }
    }
}

/**
 * Sets a whole category of settings to one specific value
 * @param string    Category
 * @param string    Value
 * @param int       Access URL. Optional. Defaults to 1
 * @param array     Optional array of filters on field type
 */
function api_set_settings_category($category, $value = null, $access_url = 1, $fieldtype = array()) {
    if (empty($category)) { return false; }
    $category = Database::escape_string($category);
    $t_s = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $access_url = (int) $access_url;
    if (empty($access_url)) { $access_url = 1; }
    if (isset($value)) {
        $value = Database::escape_string($value);
        $sql = "UPDATE $t_s SET selected_value = '$value' WHERE category = '$category' AND access_url = $access_url";
        if (is_array($fieldtype) && count($fieldtype)>0) {
            $sql .= " AND ( ";
            $i = 0;
            foreach ($fieldtype as $type){
                if ($i > 0) {
                    $sql .= ' OR ';
                }
                $type = Database::escape_string($type);
                $sql .= " type='".$type."' ";
                $i++;
            }
            $sql .= ")";
        }
        $res = Database::query($sql);
        return $res !== false;
    } else {
        $sql = "UPDATE $t_s SET selected_value = NULL WHERE category = '$category' AND access_url = $access_url";
        if (is_array($fieldtype) && count($fieldtype)>0) {
            $sql .= " AND ( ";
            $i = 0;
            foreach ($fieldtype as $type){
                if ($i > 0) {
                    $sql .= ' OR ';
                }
                $type = Database::escape_string($type);
                $sql .= " type='".$type."' ";
                $i++;
            }
            $sql .= ")";
        }
        $res = Database::query($sql);
        return $res !== false;
    }
}

/**
 * Gets all available access urls in an array (as in the database)
 * @return array    An array of database records
 */
function api_get_access_urls($from = 0, $to = 1000000, $order = 'url', $direction = 'ASC') {
    $t_au = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $from = (int) $from;
    $to = (int) $to;
    $order = Database::escape_string($order);
    $direction = Database::escape_string($direction);
    $sql = "SELECT id, url, description, active, created_by, tms FROM $t_au ORDER BY $order $direction LIMIT $to OFFSET $from";
    $res = Database::query($sql);
    return Database::store_result($res);
}

/**
 * Gets the access url info in an array
 * @param id of the access url
 * @return array Array with all the info (url, description, active, created_by, tms) from the access_url table
 * @author Julio Montoya Armas
 */
function api_get_access_url($id) {
    global $_configuration;
    $id = Database::escape_string(intval($id));
    // Calling the Database:: library dont work this is handmade.
    //$table_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $table = 'access_url';
    $database = $_configuration['main_database'];
    $table_access_url =  "".$database.".".$table."";
    $sql = "SELECT url, description, active, created_by, tms
            FROM $table_access_url WHERE id = '$id' ";
    $res = Database::query($sql);
    $result = @Database::fetch_array($res);
    return $result;
}

/**
 * Adds an access URL into the database
 * @param string    URL
 * @param string    Description
 * @param int       Active (1= active, 0=disabled)
 * @return int      The new database id, or the existing database id if this url already exists
 */
function api_add_access_url($u, $d = '', $a = 1) {
    $t_au = Database::get_main_table(TABLE_MAIN_ACCESS_URL);
    $u = Database::escape_string($u);
    $d = Database::escape_string($d);
    $a = (int) $a;
    $sql = "SELECT id FROM $t_au WHERE url LIKE '$u'";
    $res = Database::query($sql);
    if ($res === false) {
        // Problem querying the database - return false.
        return false;
    }
    if (Database::num_rows($res) > 0) {
        return Database::result($res, 0, 'id');
    }
    $ui = api_get_user_id();

    $sql = "INSERT INTO $t_au (url,description,active,created_by,tms) VALUES ('$u','$d',$a,$ui,'')";
    $res = Database::query($sql);
    return ($res === false) ? false : Database::insert_id();
}

/**
 * Gets all the current settings for a specific access url
 * @param string    The category, if any, that we want to get
 * @param string    Whether we want a simple list (display a catgeory) or a grouped list (group by variable as in settings.php default). Values: 'list' or 'group'
 * @param int       Access URL's ID. Optional. Uses 1 by default, which is the unique URL
 * @return array    Array of database results for the current settings of the current access URL
 */
function & api_get_settings($cat = null, $ordering = 'list', $access_url = 1, $url_changeable = 0) {
    $t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $access_url = (int) $access_url;
    $url_changeable_where = '';
    if ($url_changeable == 1) {
        $url_changeable_where= " AND access_url_changeable= '1' ";
    }
    if (empty($access_url) or $access_url == -1) { $access_url = 1; }
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
    $result = Database::store_result(Database::query($sql));
    return $result;
    
}

/**
 * Gets the distinct settings categories
 * @param array     Array of strings giving the categories we want to exclude
 * @param int       Access URL. Optional. Defaults to 1
 * @return array    A list of categories
 */
function & api_get_settings_categories($exceptions = array(), $access_url = 1) {
    $access_url = (int) $access_url;
    $t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $list = "'".implode("','",$exceptions)."'";
    $sql = "SELECT DISTINCT category FROM $t_cs";
    if ($list != "'',''" and $list != "''" and !empty($list)) {
        $sql .= " WHERE category NOT IN ($list)";
    }
    $result = Database::store_result(Database::query($sql));
    return $result;
}

/**
 * Deletes a setting
 * @param string    Variable
 * @param string    Subkey
 * @param int       Access URL
 * @return boolean  False on failure, true on success
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
        $r = Database::query($sql);
        return $r;
    }
    $sql = "DELETE FROM $t_cs WHERE variable = '$v' AND access_url = $a";
    $r = Database::query($sql);
    return $r;
}

/**
 * Deletes all the settings from one category
 * @param string    Category
 * @param int       Access URL
 * @return boolean  False on failure, true on success
 */
function api_delete_category_settings($c, $a = 1) {
    if (empty($c)) { return false; }
    $t_cs = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $c = Database::escape_string($c);
    $a = (int) $a;
    if (empty($a)) { $a = 1; }
    $sql = "DELETE FROM $t_cs WHERE category = '$c' AND access_url = $a";
    $r = Database::query($sql);
    return $r;
}

/**
 * Sets a platform configuration setting to a given value
 * @param string    The value we want to record
 * @param string    The variable name we want to insert
 * @param string    The subkey for the variable we want to insert
 * @param string    The type for the variable we want to insert
 * @param string    The category for the variable we want to insert
 * @param string    The title
 * @param string    The comment
 * @param string    The scope
 * @param string    The subkey text
 * @param int       The access_url for which this parameter is valid
 * @param int       The changeability of this setting for non-master urls
 * @return boolean  true on success, false on failure
 */
function api_add_setting($val, $var, $sk = null, $type = 'textfield', $c = null, $title = '', $com = '', $sc = null, $skt = null, $a = 1, $v = 0) {
    if (empty($var) || !isset($val)) { return false; }
    $t_settings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
    $var = Database::escape_string($var);
    $val = Database::escape_string($val);
    $a = (int) $a;
    if (empty($a)) { $a = 1; }
    // Check if this variable doesn't exist already
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
    $res = Database::query($select);
    if (Database::num_rows($res) > 0) { // Found item for this access_url.
        $row = Database::fetch_array($res);
        return $row['id'];
    }

    // Item not found for this access_url, we have to check if the whole thing is missing
    // (in which case we ignore the insert) or if there *is* a record but just for access_url = 1
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
    if (isset($c)) { // Category
        $c = Database::escape_string($c);
        $insert .= "'$c',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($sk)) { // Subkey
        $sk = Database::escape_string($sk);
        $insert .= "'$sk',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($title)) { // Title
        $title = Database::escape_string($title);
        $insert .= "'$title',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($com)) { // Comment
        $com = Database::escape_string($com);
        $insert .= "'$com',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($sc)) { // Scope
        $sc = Database::escape_string($sc);
        $insert .= "'$sc',";
    } else {
        $insert .= "NULL,";
    }
    if (isset($skt)) { // Subkey text
        $skt = Database::escape_string($skt);
        $insert .= "'$skt',";
    } else {
        $insert .= "NULL,";
    }
    $insert .= "$a,$v)";
    $res = Database::query($insert);
    return $res;
}

/**
 * Checks wether a user can or can't view the contents of a course.
 *
 * @param   int $userid     User id or NULL to get it from $_SESSION
 * @param   int $cid        Course id to check whether the user is allowed.
 * @return  bool
 */
function api_is_course_visible_for_user($userid = null, $cid = null) {
    if ($userid == null) {
        $userid = api_get_user_id();
    }
    if (empty($userid) || strval(intval($userid)) != $userid) {
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

    $result = Database::query($sql);

    if (Database::num_rows($result) > 0) {
        $visibility = Database::fetch_array($result);
        $visibility = $visibility['visibility'];
    } else {
        $visibility = 0;
    }
    // Shortcut permissions in case the visibility is "open to the world".
    if ($visibility === COURSE_VISIBILITY_OPEN_WORLD) {
        return true;
    }

    if (api_get_setting('use_session_mode') != 'true') {
        $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $sql = "SELECT tutor_id, status
                FROM $course_user_table
                WHERE user_id  = '$userid' AND relation_type<>".COURSE_RELATION_TYPE_RRHH."
                AND   course_code = '$cid'
                LIMIT 1";

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            // This user have a recorded state for this course.
            $cuData = Database::fetch_array($result);

            $is_courseMember = true;
            $is_courseTutor = ($cuData['tutor_id' ] == 1);
            $is_courseAdmin = ($cuData['status'] == 1);

        } else {
            // This user has no status related to this course.
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
                    relation_type <> '".COURSE_RELATION_TYPE_RRHH."'
                AND
                    course_code = '$cid'
                LIMIT 1";

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            // This user has got a recorded state for this course.
            $cuData = Database::fetch_array($result);

            $_courseUser['role'] = $cuData['role'];
            $is_courseMember     = true;
            $is_courseTutor      = ($cuData['tutor_id' ] == 1);
            $is_courseAdmin      = ($cuData['status'] == 1);
        }
        if (!$is_courseAdmin) {
            // This user has no status related to this course.
            // Is it the session coach or the session admin?
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

            $result = Database::query($sql);
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
                // Check if the current user is the course coach.
                $sql = "SELECT 1
                        FROM $tbl_session_course
                        WHERE session_rel_course.course_code = '$cid'
                        AND session_rel_course.id_coach = '$userid'
                        LIMIT 1";

                $result = Database::query($sql);

                //if ($row = Database::fetch_array($result)) {
                if (Database::num_rows($result) > 0 ) {
                    $_courseUser['role'] = 'Professor';
                    $is_courseMember = true;
                    $is_courseTutor = true;
                    $is_courseCoach = true;
                    $is_sessionAdmin = false;

                    $tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

                    $sql = "SELECT status FROM $tbl_user
                            WHERE  user_id = $userid  LIMIT 1";

                    $result = Database::query($sql);

                    if (Database::result($result, 0, 0) == 1) {
                        $is_courseAdmin = true;
                    } else {
                        $is_courseAdmin = false;
                    }
                } else {
                    // Check if the user is a student is this session.
                    $sql = "SELECT  id
                            FROM    $tbl_session_course_user
                            WHERE   id_user  = '$userid'
                            AND     course_code = '$cid'
                            LIMIT 1";

                    if (Database::num_rows($result) > 0) {
                        // This user haa got a recorded state for this course.
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

    // Get information to build query depending of the tool.
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
    $course_id = api_get_course_int_id();
    
    $sql = "SELECT session_id FROM $table_tool WHERE c_id = $course_id AND $key_field =  ".intval($element_id);
    $rs = Database::query($sql);
    if ($element_session_id = Database::result($rs, 0, 0)) {
        if ($element_session_id == intval($session_id)) { // The element belongs to the session.
            return true;
        }
    }
    return false;
}

/**
 * Replaces "forbidden" characters in a filename string.
 *
 * @author Hugues Peeters <peeters@ipm.ucl.ac.be>
 * @author René Haentjens, UGent (RH)
 * @author Ivan Tcholakov, JUN-2009.        Transliteration functionality has been added.
 * @param  string $filename                 The filename string.
 * @param  string $strict (optional)        When it is 'strict', all non-ASCII charaters will be replaced. Additional ASCII replacemets will be done too.
 * @return string                           The cleaned filename.
 */

function replace_dangerous_char($filename, $strict = 'loose') {
    // Safe replacements for some non-letter characters.
    static $search  = array("\0", ' ', "\t", "\n", "\r", "\x0B", '/', "\\", '"', "'", '?', '*', '>', '<', '|', ':', '$', '(', ')', '^', '[', ']', '#', '+', '&', '%');
    static $replace = array('',   '_', '_',  '_',  '_',  '_',    '-', '-',  '-', '_', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-');

    // Encoding detection.
    $encoding = api_detect_encoding($filename);
    // Converting html-entities into encoded characters.
    $filename = api_html_entity_decode($filename, ENT_QUOTES, $encoding);
    // Transliteration to ASCII letters, they are not dangerous for filesystems.
    $filename = api_transliterate($filename, 'x', $encoding);

    // Trimming leading/trailing whitespace.
    $filename = trim($filename);
    // Trimming any leading/trailing dots.
    $filename = trim($filename, '.');
    $filename = trim($filename);

    // Replacing remaining dangerous non-letter characters.
    $filename = str_replace($search, $replace, $filename);
    if ($strict == 'strict') {
        //$filename = str_replace('-', '_', $filename); // See task #1848.
        $filename = preg_replace('/[^0-9A-Za-z_.\-]/', '', $filename);
    }

    // Length is to be limited, so the file name to be acceptable by some operating systems.
    $extension = (string)strrchr($filename, '.');
    $extension_len = strlen($extension);
    if ($extension_len > 0 && $extension_len < 250) {
        $filename = substr($filename, 0, -$extension_len);
        return substr($filename, 0, 250 - $extension_len).$extension;
    }
    return substr($filename, 0, 250);
}

/**
 * Fixes the $_SERVER['REQUEST_URI'] that is empty in IIS6.
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
 * PEAR packages of Chamilo should be read before other external packages.
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
                    // The path of Chamilo PEAR packages is to be inserted after the current directory path.
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

/** Gets the current access_url id of the Chamilo Platform
 * @author Julio Montoya <gugli100@gmail.com>
 * @return int access_url_id of the current Chamilo Installation
 */
function api_get_current_access_url_id() {
    $access_url_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
    $path = Database::escape_string(api_get_path(WEB_PATH));
    $sql = "SELECT id FROM $access_url_table WHERE url = '".$path."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        $access_url_id = Database::result($result, 0, 0);
        return $access_url_id;
    }
    //if the url in WEB_PATH was not found, it can only mean that there is
    // either a configuration problem or the first URL has not been defined yet
    // (by default it is http://localhost/). Thus the more sensible thing we can
    // do is return 1 (the main URL) as the user cannot hack this value anyway 
    return 1;
}

/**
 * Gets the registered urls from a given user id
 * @author Julio Montoya <gugli100@gmail.com>
 * @return int user id
 */
function api_get_access_url_from_user($user_id) {
    $user_id = intval($user_id);
    $table_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $table_url          = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
    $sql = "SELECT access_url_id FROM $table_url_rel_user url_rel_user INNER JOIN $table_url u
            ON (url_rel_user.access_url_id = u.id)
            WHERE user_id = ".Database::escape_string($user_id);
    $result = Database::query($sql);
    $url_list = array();
    while ($row = Database::fetch_array($result, 'ASSOC')) {
        $url_list[] = $row['access_url_id'];
    }
    return $url_list;
}

/**
 * Gets the status of a user in a course
 * @param int       user_id
 * @param string    course_code
 * @return int      user status
 */
function api_get_status_of_user_in_course ($user_id, $course_code) {
    $tbl_rel_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
    if (!empty($user_id) && !empty($course_code)) {
        $user_id        = Database::escape_string(intval($user_id));
        $course_code    = Database::escape_string($course_code);
        $sql = 'SELECT status FROM '.$tbl_rel_course_user.'
            WHERE user_id='.$user_id.' AND course_code="'.$course_code.'";';
        $result = Database::query($sql);
        $row_status = Database::fetch_array($result, 'ASSOC');
        return $row_status['status'];
    } else {
        return 0;
    }
}

/**
 * Checks whether the curent user is in a course or not.
 *
 * @param string        The course code - optional (takes it from session if not given)
 * @return boolean
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
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
 * @param string        The group id - optional (takes it from session if not given)
 * @param string        The course code - optional (no additional check by course if course code is not given)
 * @return boolean
 * @author Ivan Tcholakov
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

/**
 * This function gets the hash in md5 or sha1 (it depends in the platform config) of a given password
 * @param  string password
 * @return string password with the applied hash
 */
function api_get_encrypted_password($password, $salt = '') {
    global $_configuration;
    $password_encryption = isset($_configuration['password_encryption']) ? $_configuration['password_encryption'] : 'sha1';
    
    switch ($password_encryption) {
        case 'sha1':
            return empty($salt) ? sha1($password) : sha1($password.$salt);
        case 'none':
            return $password;
        case 'md5':
        default:
            return empty($salt) ? md5($password)  : md5($password.$salt);
    }
}

/**
 * Checks whether a secret key is valid
 * @param string $original_key_secret  - secret key from (webservice) client
 * @param string $security_key - security key from Chamilo
 * @return boolean - true if secret key is valid, false otherwise
 */
function api_is_valid_secret_key($original_key_secret, $security_key) {
    global $_configuration;
    return $original_key_secret == sha1($security_key);
}

/**
 * Checks whether a user is into course
 * @param string $course_id - the course id
 * @param string $user_id - the user id
 */
function api_is_user_of_course($course_id, $user_id) {
    $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = 'SELECT user_id FROM '.$tbl_course_rel_user.' WHERE course_code="'.Database::escape_string($course_id).'" AND user_id="'.Database::escape_string($user_id).'" AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' ';
    $result = Database::query($sql);
    return Database::num_rows($result) == 1;
}

/**
 * Checks whether the server's operating system is Windows (TM).
 * @return boolean - true if the operating system is Windows, false otherwise
 */
function api_is_windows_os() {
    if (function_exists('php_uname')) {
        // php_uname() exists as of PHP 4.0.2, according to the documentation.
        // We expect that this function will always work for Chamilo 1.8.x.
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
 * This function informs whether the sent request is XMLHttpRequest
 */
function api_is_xml_http_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Substitute for json_encode function for PHP version < 5.2.
 *
 * The following function has been adopted from Drupal's "Image Browser" project,
 * @link http://drupal.org/project/imagebrowser
 * version 6.x-2.x-dev, 2010-Mar-11,
 * @link http://ftp.drupal.org/files/projects/imagebrowser-6.x-2.x-dev.tar.gz
 */
if (!function_exists('json_encode')) {
    function json_encode($a = false) {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a)) {
            if (is_float($a)) {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }

            if (is_string($a)) {
                static $json_replaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($json_replaces[0], $json_replaces[1], $a) . '"';
            }
            else
            return $a;
        }
        $is_list = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $is_list = false;
                break;
            }
        }
        $result = array();
        if ($is_list) {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        }
        else {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
}

/**
 * This wrapper function has been implemented for avoiding some known problems about the function getimagesize().
 * @link http://php.net/manual/en/function.getimagesize.php
 * @link http://www.dokeos.com/forum/viewtopic.php?t=12345
 * @link http://www.dokeos.com/forum/viewtopic.php?t=16355
 */
function api_getimagesize($path) {
    $image = new Image($path);    
    return $image->get_image_size();
}

/**
 * This function resizes an image, with preserving its proportions (or aspect ratio).
 * @author Ivan Tcholakov, MAY-2009.
 * @param int $image            System path or URL of the image
 * @param int $target_width     Targeted width
 * @param int $target_height    Targeted height
 * @return array                Calculated new width and height
 */
function api_resize_image($image, $target_width, $target_height) {
    $image_properties = api_getimagesize($image);       
    return api_calculate_image_size($image_properties['width'], $image_properties['height'], $target_width, $target_height);
}

/**
 * This function calculates new image size, with preserving image's proportions (or aspect ratio).
 * @author Ivan Tcholakov, MAY-2009.
 * @author The initial idea has been taken from code by Patrick Cool, MAY-2004.
 * @param int $image_width      Initial width
 * @param int $image_height     Initial height
 * @param int $target_width     Targeted width
 * @param int $target_height    Targeted height
 * @return array                Calculated new width and height
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
 * Returns a list of Chamilo's tools or
 * checks whether a given identificator is a valid Chamilo's tool.
 * @author Isaac flores paz 
 * @param string The tool name to filter
 * @return mixed Filtered string or array
 */
function api_get_tools_lists($my_tool = null) {
    $tools_list = array(
        TOOL_DOCUMENT, TOOL_THUMBNAIL, TOOL_HOTPOTATOES,
        TOOL_CALENDAR_EVENT, TOOL_LINK, TOOL_COURSE_DESCRIPTION, TOOL_SEARCH,
        TOOL_LEARNPATH, TOOL_ANNOUNCEMENT, TOOL_FORUM, TOOL_THREAD, TOOL_POST,
        TOOL_DROPBOX, TOOL_QUIZ, TOOL_USER, TOOL_GROUP, TOOL_BLOGS, TOOL_CHAT,
        TOOL_CONFERENCE, TOOL_STUDENTPUBLICATION, TOOL_TRACKING, TOOL_HOMEPAGE_LINK,
        TOOL_COURSE_SETTING, TOOL_BACKUP, TOOL_COPY_COURSE_CONTENT, TOOL_RECYCLE_COURSE,
        TOOL_COURSE_HOMEPAGE, TOOL_COURSE_RIGHTS_OVERVIEW, TOOL_UPLOAD, TOOL_COURSE_MAINTENANCE,
        TOOL_VISIO, TOOL_VISIO_CONFERENCE, TOOL_VISIO_CLASSROOM, TOOL_SURVEY, TOOL_WIKI,
        TOOL_GLOSSARY, TOOL_GRADEBOOK, TOOL_NOTEBOOK, TOOL_ATTENDANCE, TOOL_COURSE_PROGRESS
    );
    if (empty($my_tool)) {
        return $tools_list;
    }
    return in_array($my_tool, $tools_list) ? $my_tool : '';
}

/**
 * Checks whether we already approved the last version term and condition
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
        // Check the last user version_id passed
        $sqlv = "SELECT field_value FROM $t_ufv ufv inner join $t_uf uf on ufv.field_id= uf.id
                WHERE field_variable = 'legal_accept' AND user_id = ".intval($user_id);
        $resv = Database::query($sqlv);
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
 * Gets all information of a tool into course
 * @param int The tool id
 * @return array
 */
function api_get_tool_information($tool_id) {
    $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
    $course_id = api_get_course_int_id();
    $sql = "SELECT * FROM $t_tool WHERE c_id = $course_id AND id = ".intval($tool_id);
    $rs  = Database::query($sql);
    return Database::fetch_array($rs);
}

/**
 * Gets all information of a tool into course
 * @param int The tool id
 * @return array
 */
function api_get_tool_information_by_name($name) {
    $t_tool = Database::get_course_table(TABLE_TOOL_LIST);
    $course_id = api_get_course_int_id();
    $sql = "SELECT * FROM $t_tool WHERE c_id = $course_id  AND name = '".Database::escape_string($name)."' ";
    $rs  = Database::query($sql);
    return Database::fetch_array($rs, 'ASSOC');
}


/* DEPRECATED FUNCTIONS */

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
 * deprecated: use api_is_allowed_to_edit() instead
 */
function is_allowed_to_edit() {
    return api_is_allowed_to_edit();
}

/**
 * deprecated: 19-SEP-2009: Use api_get_path(TO_SYS, $url) instead.
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

/**
 * @deprecated 25-JAN-2010: See api_mail() and api_mail_html(), mail.lib.inc.php
 *
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

    require_once api_get_path(LIBRARY_PATH).'phpmailer/class.phpmailer.php';

    if (empty($platform_email['SMTP_FROM_NAME'])) {
        $platform_email['SMTP_FROM_NAME'] = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
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

    // Regular expression to test for valid email address.
    // This should actually be revised to use the complete RFC3696 description.
    // http://tools.ietf.org/html/rfc3696#section-3
    //$regexp = "^[0-9a-z_\.+-]+@(([0-9]{1,3}\.){3}[0-9]{1,3}|([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,3})$"; // Deprecated, 13-OCT-2010.

    $mail = new PHPMailer();
    $mail->CharSet = $platform_email['SMTP_CHARSET'];
    $mail->Mailer = $platform_email['SMTP_MAILER'];
    $mail->Host = $platform_email['SMTP_HOST'];
    $mail->Port = $platform_email['SMTP_PORT'];

    if ($platform_email['SMTP_AUTH']) {
        $mail->SMTPAuth = 1;
        $mail->Username = $platform_email['SMTP_USER'];
        $mail->Password = $platform_email['SMTP_PASS'];
    }

    $mail->Priority = 3; // 5 = low, 1 = high
    $mail->AddCustomHeader('Errors-To: '.$platform_email['SMTP_FROM_EMAIL']);
    $mail->IsHTML(0);
    $mail->SMTPKeepAlive = true;

    // Attachments.
    // $mail->AddAttachment($path);
    // $mail->AddAttachment($path, $filename);

    if ($sender_email != '') {
        $mail->From = $sender_email;
        $mail->Sender = $sender_email;
        //$mail->ConfirmReadingTo = $sender_email; // Disposition-Notification
    } else {
        $mail->From = $platform_email['SMTP_FROM_EMAIL'];
        $mail->Sender = $platform_email['SMTP_FROM_EMAIL'];
        //$mail->ConfirmReadingTo = $platform_email['SMTP_FROM_EMAIL']; // Disposition-Notification
    }

    if ($sender_name != '') {
        $mail->FromName = $sender_name;
    } else {
        $mail->FromName = $platform_email['SMTP_FROM_NAME'];
    }
    $mail->Subject = $subject;
    $mail->Body = $message;
    // Only valid address are to be accepted.
    //if (eregi( $regexp, $recipient_email )) { // Deprecated, 13-OCT-2010.
    if (api_valid_email($recipient_email)) {
        $mail->AddAddress($recipient_email, $recipient_name);
    }

    if ($extra_headers != '') {
        $mail->AddCustomHeader($extra_headers);
    }

    // Send mail.
    if (!$mail->Send()) {
        return 0;
    }

    // Clear all the addresses.
    $mail->ClearAddresses();
    return 1;
}

/* END OF DEPRECATED FUNCTIONS SECTION */


/**
 * Function used to protect a "global" admin script.
 * The function blocks access when the user has no global platform admin rights.
 * Global admins are the admins that are registered in the main.admin table AND the users who have access to the "principal" portal.
 * That means that there is a record in the main.access_url_rel_user table with his user id and the access_url_id=1
 *
 * @author Julio Montoya
 */
function api_is_global_platform_admin() {
    if (api_is_platform_admin()) {
        $my_url_list = api_get_access_url_from_user(api_get_user_id());
        // The admin is registered in the first "main" site with access_url_id = 1
        if (in_array(1, $my_url_list)) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}


/**
 * Function used to protect a global admin script.
 * The function blocks access when the user has no global platform admin rights.
 * See also the api_is_global_platform_admin() function wich defines who's a "global" admin
 *
 * @author Julio Montoya
 */
function api_protect_global_admin_script() {
    if (!api_is_global_platform_admin()) {        
        api_not_allowed();
        return false;
    }
    return true;
}

/**
 * Get actived template
 * @param string    theme type (optional: default)
 * @param string    path absolute(abs) or relative(rel) (optional:rel)
 * @return string   actived template path
 */
function api_get_template($path_type = 'rel') {
    $path_types = array('rel', 'abs');
    $template_path = '';
    if (in_array($path_type, $path_types)) {
        if ($path_type == 'rel') {
            $template_path = api_get_path(SYS_TEMPLATE_PATH);
        } else {
            $template_path = api_get_path(WEB_TEMPLATE_PATH);
        }
    }
    $actived_theme = 'default';
    if (api_get_setting('active_template')) {
        $actived_theme = api_get_setting('active_template');
    }
    $actived_theme_path = $template_path.$actived_theme.DIRECTORY_SEPARATOR;
    return $actived_theme_path;
}

/**
 * Check browser support for type files
 ** This function check if the users browser support a file format or return the current browser and major ver when $format=check_browser
 * @param string $format
 *
 * @return bool, or return text array if $format=check_browser
 * 
 * @author Juan Carlos Raña Trabado
 */
 
function api_browser_support($format="") {
    require_once api_get_path(LIBRARY_PATH).'browser/Browser.php';
    $browser = new Browser();
    //print_r($browser);
    $current_browser = $browser->getBrowser();
    $a_versiontemp = explode('.', $browser->getVersion());
    $current_majorver= $a_versiontemp[0];
	
	//native svg support
	if ($format=='svg'){
		if (($current_browser == 'Internet Explorer' && $current_majorver >= 9) || ($current_browser == 'Firefox' && $current_majorver > 1) || ($current_browser == 'Safari' && $current_majorver >= 4) || ($current_browser == 'Chrome' && $current_majorver >= 1) || ($current_browser == 'Opera' && $current_majorver >= 9)) {
			return true;
		}
		else {
			return false;
		}
	}
	elseif($format=='pdf'){
		//native pdf support
		if($current_browser == 'Chrome' && $current_majorver >= 6){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='tif' || $format=='tiff'){
		//native tif support
		if($current_browser == 'Safari' && $current_majorver >= 5){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='ogg' || $format=='ogx'|| $format=='ogv' || $format=='oga'){
	//native ogg, ogv,oga support
		if (($current_browser == 'Firefox' && $current_majorver >= 3)  || ($current_browser == 'Chrome' && $current_majorver >= 3) || ($current_browser == 'Opera' && $current_majorver >= 9)) {			
			return true;
		}
		else {
			return false;
		}		
	}
	elseif($format=='mpg' || $format=='mpeg'){
		//native mpg support
		if(($current_browser == 'Safari' && $current_majorver >= 5)){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='mp4'){
		//native mp4 support (TODO: Android, iPhone)
		if($current_browser == 'Android' || $current_browser == 'iPhone') {
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='mov'){
		//native mov support( TODO:check iPhone)
		if($current_browser == 'Safari' && $current_majorver >= 5 || $current_browser == 'iPhone'){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='avi'){
		//native avi support
		if($current_browser == 'Safari' && $current_majorver >= 5){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='wmv'){
		//native wmv support
		if($current_browser == 'Firefox' && $current_majorver >= 4){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='webm'){
		//native webm support (TODO:check IE9, Chrome9, Android)
		if(($current_browser == 'Firefox' && $current_majorver >= 4) || ($current_browser == 'Opera' && $current_majorver >= 9) || ($current_browser == 'Internet Explorer' && $current_majorver >= 9)|| ($current_browser == 'Chrome' && $current_majorver >=9)|| $current_browser == 'Android'){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='wav'){
		//native wav support (only some codecs !)
		if(($current_browser == 'Firefox' && $current_majorver >= 4) || ($current_browser == 'Safari' && $current_majorver >= 5) || ($current_browser == 'Opera' && $current_majorver >= 9) || ($current_browser == 'Internet Explorer' && $current_majorver >= 9)|| ($current_browser == 'Chrome' && $current_majorver > 9)|| $current_browser == 'Android' || $current_browser == 'iPhone'){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='mid' || $format=='kar'){
		//native midi support (TODO:check Android)
		if($current_browser == 'Opera'&& $current_majorver >= 9 || $current_browser == 'Android'){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='wma'){
		//native wma support
		if($current_browser == 'Firefox' && $current_majorver >= 4){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='au'){
		//native au support 
		if($current_browser == 'Safari' && $current_majorver >= 5){
			return true;
		}
		else{
			return false;
		}
	}
	elseif($format=='mp3'){
		//native mp3 support (TODO:check Android, iPhone)
		if(($current_browser == 'Safari' && $current_majorver >= 5) || ($current_browser == 'Chrome' && $current_majorver >=6)|| ($current_browser == 'Internet Explorer' && $current_majorver >= 9)|| $current_browser == 'Android' || $current_browser == 'iPhone'){
			return true;
		}
		else{
			return false;
		}
	}	
	elseif($format=="check_browser"){
		$array_check_browser=array($current_browser, $current_majorver);		
		return $array_check_browser;
	}
	else{
		return false;
	}
}

/**
 * This function checks if exist path and file browscap.ini
 * In order for this to work, your browscap configuration setting in php.ini must point to the correct location of the browscap.ini file on your system
 * http://php.net/manual/en/function.get-browser.php
 * 
 * @return bool
 *
 * @author Juan Carlos Raña Trabado
 */
function api_check_browscap() {
    $setting = ini_get('browscap');
    
    if ($setting) {
        $browser = get_browser($_SERVER['HTTP_USER_AGENT'], true);
	    if (strpos($setting, 'browscap.ini') && !empty($browser)) {
	        return true;
	    }
    }
    return false;	    
}



function api_get_js($file) {    
    return '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/'.$file.'"></script>';
}

function api_get_css($file) {
	$media = '';
	return '<link rel="stylesheet" href="'.$file.'" type="text/css" media="'.$media.'" />';	
}



/**
 * Returns the js header to include the jquery library
 */
function api_get_jquery_js() {
    return api_get_js('jquery.min.js');	
}


/**
 * Returns the jquery-ui library js headers 
 * @param   bool    add the jqgrid library
 * @return  string  html tags
 * 
 */
function api_get_jquery_ui_js($include_jqgrid = false) {
    $libraries[]='jquery-ui';
	if ($include_jqgrid) {
	   $libraries[]='jqgrid';	
	}
    return api_get_jquery_libraries_js($libraries);
}

/**
 * Returns the jquery library js and css headers 
 * 
 * @param   array   list of jquery libraries supported jquery-ui, jqgrid 
 * @param   bool    add the jquery library
 * @return  string  html tags
 * 
 */
function api_get_jquery_libraries_js($libraries) {
    $js = '';
    $js_path = api_get_path(WEB_LIBRARY_PATH).'javascript/';    
    
    //jquery-ui js and css
    if (in_array('jquery-ui', $libraries)) {
        //Jquery ui
        $theme = 'smoothness'; // Current themes: cupertino, smoothness, ui-lightness. Find the themes folder in main/inc/lib/javascript/jquery-ui
        
        $jquery_ui_version = '1.8.16';
        
        //$js .= '<link rel="stylesheet" href="'.$js_path.'jquery-ui/'.$theme.'/jquery-ui-'.$jquery_ui_version.'.custom.css" type="text/css">';
        $js .= api_get_css($js_path.'jquery-ui/'.$theme.'/jquery-ui-'.$jquery_ui_version.'.custom.css');
        $js .= api_get_js('jquery-ui/'.$theme.'/jquery-ui-'.$jquery_ui_version.'.custom.min.js');
    }
    
    //jqgrid js and css
    if (in_array('jqgrid', $libraries)) { 
        $languaje   = 'en';
        $platform_isocode = strtolower(api_get_language_isocode());
         
        //languages supported by jqgrid see files in main/inc/lib/javascript/jqgrid/js/i18n
        $jqgrid_langs = array('bg', 'bg1251', 'cat','cn','cs','da','de','el','en','es','fa','fi','fr','gl','he','hu','is','it','ja','nl','no','pl','pt-br','pt','ro','ru','sk','sr','sv','tr','ua');
         
        if (in_array($platform_isocode, $jqgrid_langs)) {
            $languaje = $platform_isocode;
        }    
        //$js .= '<link rel="stylesheet" href="'.$js_path.'jqgrid/css/ui.jqgrid.css" type="text/css">';
        $js .= api_get_css($js_path.'jqgrid/css/ui.jqgrid.css');
        $js .= api_get_js('jqgrid/js/i18n/grid.locale-'.$languaje.'.js');
        $js .= api_get_js('jqgrid/js/jquery.jqGrid.min.js');
    }
    
    //Document multiple upload funcionality
    if (in_array('jquery-upload', $libraries)) {
        $js .= api_get_js('jquery-upload/jquery.fileupload.js'); 
        $js .= api_get_js('jquery-upload/jquery.fileupload-ui.js');
        $js .= api_get_css($js_path.'jquery-upload/jquery.fileupload-ui.css');                
    }
    
    //jquery-ui css changes for Chamilo
    if (in_array('jquery-ui',$libraries)) {
        //Adding default CSS changes of the jquery-ui themes for Chamilo in order to preserve the original jquery-ui css        
        $js .= api_get_css($js_path.'jquery-ui/default.css');
    }
    
    if (in_array('bxslider',$libraries)) {
    	$js .= api_get_js('bxslider/jquery.bxSlider.min.js');
    	$js .= api_get_css($js_path.'bxslider/bx_styles/bx_styles.css');    	
    }    
    return $js;	
}


/**
 * Returns the course's URL 
 *
 * This function relies on api_get_course_info() 
 * @param 	string  The course code - optional (takes it from session if not given)
 * @param 	int		The session id  - optional (takes it from session if not given)
 * @return 	mixed 	The URL of the course or null if something does not work  
 * @author 	Julio Montoya <gugli100@gmail.com>
 */

function api_get_course_url($course_code = null, $session_id = null) {
    $session_url = '';
    if (empty($course_code)) {
        $course_info = api_get_course_info();
    } else {
        $course_info = api_get_course_info($course_code);
    }
    if (empty($session_id)) {
        if (api_get_session_id() != 0)
        $session_url = '?id_session='.api_get_session_id();        
    } else {
        $session_url = '?id_session='.intval($session_id);
    }
    if (!empty($course_info['path'])) {
        return api_get_path(WEB_COURSE_PATH).$course_info['path'].'/'.$session_url;
    }
    return null;
}

/**
 * 
 * Check if the current portal has the $_configuration['multiple_access_urls'] parameter on
 * @return bool	true if multi site is enabled
 * 
 * */
function api_get_multiple_access_url() { 
    global $_configuration;
    if (isset($_configuration['multiple_access_urls']) && $_configuration['multiple_access_urls']) {
        return true;
    }
    return false;    
}

/**
 * Returns a md5 unique id
 * @todo add more parameters 
 */

function api_get_unique_id() {    
    $id = md5(time().uniqid().api_get_user_id().api_get_course_id().api_get_session_id());    
    return $id;
}



function api_get_home_path() {
	$home = 'home/';	
	if (api_get_multiple_access_url()) {
		$access_url_id = api_get_current_access_url_id();
		$url_info      = api_get_access_url($access_url_id);
		$url           = api_remove_trailing_slash(preg_replace('/https?:\/\//i', '', $url_info['url']));
		$clean_url     = replace_dangerous_char($url);
		$clean_url     = str_replace('/', '-', $clean_url);
		$clean_url     .= '/';
		// if $clean_url ==  "localhost/" means that the multiple URL was not well configured we don't rename the $home variable
		if ($clean_url != 'localhost/')
			$home          = 'home/'.$clean_url;
	}
	return $home;
}


function api_get_course_table_condition($and = true) {
	$course_id = api_get_course_int_id();
	$condition = '';
	$condition_add = $and ? " AND " : " WHERE ";
	if (!empty($course_id)) {
		$condition = " $condition_add c_id = $course_id";
	}
	return $condition;
}

function api_grading_model_functions($grading_model, $action = 'to_array') {
	$return = null;
	if (empty($grading_model)) {
		return null;
	}
	$elements = array('A','B','C','D','E','F','G','H','I','J','K','L','M');
	
	if (in_array($action, array('to_array', 'decorate'))) {	
		$parts = explode('/', $grading_model);
		$return['denominator'] = $parts[1];
		$sums = explode('+', $parts[0]);

		$j = 0;
		foreach($sums as $item) {
			$item = explode('*', $item);
			$per = 0;
			if (!empty($return['denominator'])) {
				$per = $item[0]/$return['denominator']*100;
			}
			$return['items'][] =array(	'letter'	=> $elements[$j], 
										'value'		=> $item[0], 
										'percentage'=> $per.'%');
			$j++;
		}
		
		if ($action == 'decorate') {			
			$return_value = '';
			$items = $return['items'];
			$j = 1;
			foreach ($items as $item) {
				$letter = $item['letter'];
				$value  = $item['value'];
				
				$sum = '+';
				if (count($items) == $j){
					$sum = '';
				}
				$return_value = $return_value.' '.$value.'*'.$letter." $sum ";
				$j++;							
			}			
			$return = ' ('.$return_value.') / '.$return['denominator'];
		}
	}
	return $return;
}
