<?php
/* For licensing terms, see /license.txt */

/**
 * It is recommended that ALL Chamilo scripts include this important file.
 * This script manages
 * - http get, post, post_files, session, server-vars extraction into global namespace;
 *   (which doesn't occur anymore when servertype config setting is set to test,
 *    and which will disappear completely in Dokeos 1.6.1)
 * - include of /conf/configuration.php;
 * - include of several libraries: main_api, database, display, text, security;
 * - selecting the main database;
 * - include of language files.
 *
 * @package chamilo.include
 * @todo isn't configuration.php renamed to configuration.inc.php yet?
 * @todo use the $_configuration array for all the needed variables
 * @todo remove the code that displays the button that links to the install page
 * 		but use a redirect immediately. By doing so the $already_installed variable can be removed.
 * @todo make it possible to enable / disable the tracking through the Chamilo config page.
 *
 */

// Showing/hiding error codes in global error messages.
define('SHOW_ERROR_CODES', false);

// PHP version requirement.
define('REQUIRED_PHP_VERSION', '5');

// Determine the directory path where this current file lies.
// This path will be useful to include the other intialisation files.
$includePath = dirname(__FILE__);

// PHP version check.
if (!function_exists('version_compare') || version_compare(phpversion(), REQUIRED_PHP_VERSION, '<')) {
    $global_error_code = 1;
    // Incorrect PHP version.
    require $includePath.'/global_error_message.inc.php';
    die();
}

// @todo Isn't this file renamed to configuration.inc.php yet?
// Include the main Chamilo platform configuration file.
$main_configuration_file_path = $includePath.'/conf/configuration.php';

$already_installed = false;
if (file_exists($main_configuration_file_path)) {
    require_once $main_configuration_file_path;
    $already_installed = true;
} else {
    $_configuration = array();
}

//Redirects to the main/install/ page
if (!$already_installed) {
    $global_error_code = 2;
    // The system has not been installed yet.
    require $includePath.'/global_error_message.inc.php';
    die();
}

// Ensure that _configuration is in the global scope before loading
// main_api.lib.php. This is particularly helpful for unit tests
if (!isset($GLOBALS['_configuration'])) {
    $GLOBALS['_configuration'] = $_configuration;
}

// Code for trnasitional purposes, it can be removed right before the 1.8.7 release.
if (empty($_configuration['system_version'])) {
    $_configuration['system_version']   = $_configuration['dokeos_version'];
    $_configuration['system_stable']    = $_configuration['dokeos_stable'];
    $_configuration['software_url']     = 'http://www.chamilo.org/';
}

// For backward compatibility.
$_configuration['dokeos_version']       = $_configuration['system_version'];
$_configuration['dokeos_stable']        = $_configuration['system_stable'];

// Include the main Chamilo platform library file.
require_once $includePath.'/lib/main_api.lib.php';

// Do not over-use this variable. It is only for this script's local use.
$lib_path = api_get_path(LIBRARY_PATH);

// Fix bug in IIS that doesn't fill the $_SERVER['REQUEST_URI'].
api_request_uri();

// Add the path to the pear packages to the include path
ini_set('include_path', api_create_include_path_setting());

// This is for compatibility with MAC computers.
ini_set('auto_detect_line_endings', '1');

// Include the libraries that are necessary everywhere
require_once dirname(__FILE__).'/autoload.inc.php';

require_once $lib_path.'database.lib.php';
require_once $lib_path.'template.lib.php';
require_once $lib_path.'display.lib.php';
require_once $lib_path.'text.lib.php';
require_once $lib_path.'image.lib.php';
require_once $lib_path.'array.lib.php';
require_once $lib_path.'security.lib.php';
require_once $lib_path.'events.lib.inc.php';
require_once $lib_path.'debug.lib.php';
require_once $lib_path.'rights.lib.php';

require_once $lib_path.'model.lib.php';
require_once $lib_path.'sortabletable.class.php';
require_once $lib_path.'usermanager.lib.php';
require_once $lib_path.'message.lib.php';
require_once $lib_path.'social.lib.php';
require_once $lib_path.'notification.lib.php';
require_once $lib_path.'course.lib.php';
require_once $lib_path.'sessionmanager.lib.php';
require_once $lib_path.'tracking.lib.php';

require_once $lib_path.'formvalidator/FormValidator.class.php';
require_once $lib_path.'online.inc.php';

//Here we load the new Doctrine class (just for tests)
//require_once $lib_path.'db.lib.php';
//$db = new db();

/*  DATABASE CONNECTION  */

// @todo: this shouldn't be done here. It should be stored correctly during installation.
if (empty($_configuration['statistics_database']) && $already_installed) {
    $_configuration['statistics_database'] = $_configuration['main_database'];
}
global $database_connection;
// Connect to the server database and select the main chamilo database.
if (!($conn_return = @Database::connect(
    array(
        'server'        => $_configuration['db_host'],
        'username'      => $_configuration['db_user'],
        'password'      => $_configuration['db_password'],
        'persistent'    => $_configuration['db_persistent_connection'] // When $_configuration['db_persistent_connection'] is set, it is expected to be a boolean type.
    )))) {
    $global_error_code = 3;
    // The database server is not available or credentials are invalid.
    require $includePath.'/global_error_message.inc.php';
    die();
}
if (!$_configuration['db_host']) {
    $global_error_code = 4;
    // A configuration option about database server is missing.
    require $includePath.'/global_error_message.inc.php';
    die();
}

/* RETRIEVING ALL THE CHAMILO CONFIG SETTINGS FOR MULTIPLE URLs FEATURE*/
if (!empty($_configuration['multiple_access_urls'])) {
    $_configuration['access_url'] = 1;
    $access_urls = api_get_access_urls();

    $protocol = ((!empty($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) != 'OFF') ? 'https' : 'http').'://';
    $request_url1 = $protocol.$_SERVER['SERVER_NAME'].'/';
    $request_url2 = $protocol.$_SERVER['HTTP_HOST'].'/';

    foreach ($access_urls as & $details) {
        if ($request_url1 == $details['url'] or $request_url2 == $details['url']) {
            $_configuration['access_url'] = $details['id'];
        }
    }
} else {
    $_configuration['access_url'] = 1;
}

// The system has not been designed to use special SQL modes that were introduced since MySQL 5.
Database::query("set session sql_mode='';");

if (!Database::select_db($_configuration['main_database'], $database_connection)) {
    $global_error_code = 5;
    // Connection to the main Chamilo database is impossible, it might be missing or restricted or its configuration option might be incorrect.
    require $includePath.'/global_error_message.inc.php';
    die();
}

/*   Initialization of the default encodings */
// The platform's character set must be retrieved at this early moment.
$sql = "SELECT selected_value FROM settings_current WHERE variable = 'platform_charset';";
$result = Database::query($sql);
while ($row = @Database::fetch_array($result)) {
    $charset = $row[0];
}
if (empty($charset)) {
    $charset = 'UTF-8';
}
// Preserving the value of the global variable $charset.
$charset_initial_value = $charset;

// Initialization of the internationalization library.
api_initialize_internationalization();
// Initialization of the default encoding that will be used by the multibyte string routines in the internationalization library.
api_set_internationalization_default_encoding($charset);

//setting_gettext();

// Initialization of the database encoding to be used.
Database::query("SET SESSION character_set_server='utf8';");
Database::query("SET SESSION collation_server='utf8_general_ci';");

if (api_is_utf8($charset)) {
    // See Bug #1802: For UTF-8 systems we prefer to use "SET NAMES 'utf8'" statement in order to avoid a bizarre problem with Chinese language.
    Database::query("SET NAMES 'utf8';");
} else {
    Database::query("SET CHARACTER SET '" . Database::to_db_encoding($charset) . "';");
}

// Start session after the internationalization library has been initialized.
api_session_start($already_installed);

// Remove quotes added by PHP  - get_magic_quotes_gpc() is deprecated in PHP 5 see #2970
 
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {	
	array_walk_recursive_limited($_GET,     	'stripslashes', true);	
	array_walk_recursive_limited($_POST, 	'stripslashes', true);
	array_walk_recursive_limited($_COOKIE,  'stripslashes', true);
	array_walk_recursive_limited($_REQUEST, 'stripslashes', true);
}

// access_url == 1 is the default chamilo location
if ($_configuration['access_url'] != 1) {
    $url_info = api_get_access_url($_configuration['access_url']);
    if ($url_info['active'] == 1) {
        $settings_by_access = & api_get_settings(null, 'list', $_configuration['access_url'], 1);
        foreach ($settings_by_access as & $row) {
            if (empty($row['variable'])) {
                $row['variable'] = 0;
            }
            if (empty($row['subkey'])) {
                $row['subkey'] = 0;
            }
            if (empty($row['category'])) {
                $row['category'] = 0;
            }
            $settings_by_access_list[$row['variable']][$row['subkey']][$row['category']] = $row;
        }
    }
}

$result = & api_get_settings(null, 'list', 1);
foreach ($result as & $row) {
    if ($_configuration['access_url'] != 1) {
        if ($url_info['active'] == 1) {
            $var = empty($row['variable']) ? 0 : $row['variable'];
            $subkey = empty($row['subkey']) ? 0 : $row['subkey'];
            $category = empty($row['category']) ? 0 : $row['category'];
        }

        if ($row['access_url_changeable'] == 1 && $url_info['active'] == 1) {
            if ($settings_by_access_list[$var][$subkey][$category]['selected_value'] != '') {
                if ($row['subkey'] == null) {
                    $_setting[$row['variable']] = $settings_by_access_list[$var][$subkey][$category]['selected_value'];
                } else {
                    $_setting[$row['variable']][$row['subkey']] = $settings_by_access_list[$var][$subkey][$category]['selected_value'];
                }
            } else {
                if ($row['subkey'] == null) {
                    $_setting[$row['variable']] = $row['selected_value'];
                } else {
                    $_setting[$row['variable']][$row['subkey']] = $row['selected_value'];
                }
            }
        } else {
            if ($row['subkey'] == null) {
                $_setting[$row['variable']] = $row['selected_value'];
            } else {
                $_setting[$row['variable']][$row['subkey']] = $row['selected_value'];
            }
        }
    } else {
        if ($row['subkey'] == null) {
            $_setting[$row['variable']] = $row['selected_value'];
        } else {
            $_setting[$row['variable']][$row['subkey']] = $row['selected_value'];
        }
    }
}

$result = & api_get_settings('Plugins', 'list', $_configuration['access_url']);
$_plugins = array();
foreach ($result as & $row) {
    $key = & $row['variable'];
    if (is_string($_setting[$key])) {
        $_setting[$key] = array();
    }
    $_setting[$key][] = $row['selected_value'];
    $_plugins[$key][] = $row['selected_value'];
}

// Load allowed tag definitions for kses and/or HTMLPurifier.
require_once $lib_path.'formvalidator/Rule/allowed_tags.inc.php';
// Load HTMLPurifier.
//require_once $lib_path.'htmlpurifier/library/HTMLPurifier.auto.php'; // It will be loaded later, in a lazy manner.

// Before we call local.inc.php, let's define a global $this_section variable 
// which will then be usable from the banner and header scripts
$this_section = SECTION_GLOBAL;

// include the local (contextual) parameters of this course or section
require $includePath.'/local.inc.php';

//Include Chamilo Mail conf this is added here because the api_get_setting works
$mail_conf = api_get_path(CONFIGURATION_PATH).'mail.conf.php';
if (file_exists($mail_conf)) {
	require_once $mail_conf; 
}

// ===== "who is logged in?" module section =====


// check and modify the date of user in the track.e.online table
if (!$x = strpos($_SERVER['PHP_SELF'], 'whoisonline.php')) {
    LoginCheck(isset($_user['user_id']) ? $_user['user_id'] : '');
}

// ===== end "who is logged in?" module section =====

if (api_get_setting('server_type') == 'test') {
    /*
        Server type is test
    - high error reporting level
    - only do addslashes on $_GET and $_POST
    */
    if (IS_PHP_53) {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    } else {
        error_reporting(E_ALL & ~E_NOTICE);
    }
} else {
    /*
    Server type is not test
    - normal error reporting level
    - full fake register globals block
    */
    error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);

    // TODO: These obsolete variables $HTTP_* to be check whether they are actually used.
    if (!isset($HTTP_GET_VARS)) { $HTTP_GET_VARS = $_GET; }
    if (!isset($HTTP_POST_VARS)) { $HTTP_POST_VARS = $_POST; }
    if (!isset($HTTP_POST_FILES)) { $HTTP_POST_FILES = $_FILES; }
    if (!isset($HTTP_SESSION_VARS)) { $HTTP_SESSION_VARS = $_SESSION; }
    if (!isset($HTTP_SERVER_VARS)) { $HTTP_SERVER_VARS = $_SERVER; }

    // Register SESSION variables into $GLOBALS
    if (sizeof($HTTP_SESSION_VARS)) {
        if (!is_array($_SESSION)) {
            $_SESSION = array();
        }
        foreach ($HTTP_SESSION_VARS as $key => $val) {
            $_SESSION[$key] = $HTTP_SESSION_VARS[$key];
            $GLOBALS[$key] = $HTTP_SESSION_VARS[$key];
        }
    }

    // Register SERVER variables into $GLOBALS
    if (sizeof($HTTP_SERVER_VARS)) {
        $_SERVER = array();
        foreach ($HTTP_SERVER_VARS as $key => $val) {
            $_SERVER[$key] = $HTTP_SERVER_VARS[$key];
            if (!isset($_SESSION[$key]) && $key != 'includePath' && $key != 'rootSys' && $key!= 'lang_path' && $key!= 'extAuthSource' && $key!= 'thisAuthSource' && $key!= 'main_configuration_file_path' && $key!= 'phpDigIncCn' && $key!= 'drs') {
                $GLOBALS[$key]=$HTTP_SERVER_VARS[$key];
            }
        }
    }
}

/*	LOAD LANGUAGE FILES SECTION */

// if we use the javascript version (without go button) we receive a get
// if we use the non-javascript version (with the go button) we receive a post
$user_language = '';
if (!empty($_GET['language'])) {
    $user_language = $_GET['language'];
}

if (!empty($_POST['language_list'])) {
    $user_language = str_replace('index.php?language=', '', $_POST['language_list']);
}

// Include all files (first english and then current interface language)

$langpath = api_get_path(SYS_LANG_PATH);

/* This will only work if we are in the page to edit a sub_language */
if (api_get_self() == api_get_path(REL_PATH).'main/admin/sub_language.php' || api_get_self() == api_get_path(REL_PATH).'main/admin/sub_language_ajax.inc.php') {
    require_once '../admin/sub_language.class.php';
    // getting the arrays of files i.e notification, trad4all, etc
    $language_files_to_load = SubLanguageManager:: get_lang_folder_files_list(api_get_path(SYS_LANG_PATH).'english', true);
    //getting parent info
    $parent_language = SubLanguageManager::get_all_information_of_language(intval($_REQUEST['id']));
    //getting sub language info
    $sub_language = SubLanguageManager::get_all_information_of_language(intval($_REQUEST['sub_language_id']));

    $english_language_array = $parent_language_array = $sub_language_array = array();

    foreach ($language_files_to_load as $language_file_item) {
        $lang_list_pre = array_keys($GLOBALS);
        include $langpath.'english/'.$language_file_item.'.inc.php';			 //loading english
        $lang_list_post = array_keys($GLOBALS);
        $lang_list_result = array_diff($lang_list_post, $lang_list_pre);
        unset($lang_list_pre);

        //  english language array
        $english_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach($lang_list_result as $item) {
            unset(${$item});
        }
        $parent_file = $langpath.$parent_language['dokeos_folder'].'/'.$language_file_item.'.inc.php';
        if (is_file($parent_file)) {
            include_once $parent_file;
        }

        //  parent language array
        $parent_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach($lang_list_result as $item) {
            unset(${$item});
        }
        $sub_file = $langpath.$sub_language['dokeos_folder'].'/'.$language_file_item.'.inc.php';
        if (is_file($sub_file)) {
            include $sub_file;
        }

        //  sub language array
        $sub_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach($lang_list_result as $item) {
            unset(${$item});
        }
    }
}

// Checking if we have a valid language. If not we set it to the platform language.

$valid_languages = api_get_languages();

if (!empty($valid_languages)) {

    if (!in_array($user_language, $valid_languages['folder'])) {
        $user_language = api_get_setting('platformLanguage');
    }
    $language_priority1 = api_get_setting('languagePriority1');
    $language_priority2 = api_get_setting('languagePriority2');
    $language_priority3 = api_get_setting('languagePriority3');
    $language_priority4 = api_get_setting('languagePriority4');

    if (in_array($user_language, $valid_languages['folder']) && (isset($_GET['language']) || isset($_POST['language_list']))) {
        $user_selected_language = $user_language; // $_GET['language'];
        $_SESSION['user_language_choice'] = $user_selected_language;
        $platformLanguage = $user_selected_language;
    }

    if (!empty($language_priority4) && api_get_language_from_type($language_priority4) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority4);
    } else {
        $language_interface = api_get_setting('platformLanguage');
    }

    if (!empty($language_priority3) && api_get_language_from_type($language_priority3) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority3);
    } else {
        if (isset($_SESSION['user_language_choice'])) {
            $language_interface = $_SESSION['user_language_choice'];
        }
    }

    if (!empty($language_priority2) && api_get_language_from_type($language_priority2) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority2);
    } else {
        if (isset($_user['language'])) {
            $language_interface = $_user['language'];
        }
    }
    if (!empty($language_priority1) && api_get_language_from_type($language_priority1) !== false ) {
        $language_interface =  api_get_language_from_type($language_priority1);
    } else {
        if ($_course['language']) {
            $language_interface = $_course['language'];
        }
    }
}
  
// Sometimes the variable $language_interface is changed
// temporarily for achieving translation in different language.
// We need to save the genuine value of this variable and
// to use it within the function get_lang(...).
$language_interface_initial_value = $language_interface;

/**
 * Include all necessary language files
 * - trad4all
 * - notification
 * - custom tool language files
 */
$language_files = array();
$language_files[] = 'trad4all';
$language_files[] = 'notification';
$language_files[] = 'accessibility';

if (isset($language_file)) {
    if (!is_array($language_file)) {
        $language_files[] = $language_file;
    } else {
        $language_files = array_merge($language_files, $language_file);
    }
}
// if a set of language files has been properly defined
if (is_array($language_files)) {
    // if the sub-language feature is on
    if (api_get_setting('allow_use_sub_language') == 'true') {
        require_once api_get_path(SYS_CODE_PATH).'admin/sub_language.class.php';
        $parent_path = SubLanguageManager::get_parent_language_path($language_interface);
        foreach ($language_files as $index => $language_file) {
            // include English
            include $langpath.'english/'.$language_file.'.inc.php';
            // prepare string for current language and its parent
            $lang_file = $langpath.$language_interface.'/'.$language_file.'.inc.php';
            $parent_lang_file = $langpath.$parent_path.'/'.$language_file.'.inc.php';
            // load the parent language file first
            if (file_exists($parent_lang_file)) {
                include $parent_lang_file;
            }
            // overwrite the parent language translations if there is a child
            if (file_exists($lang_file)) {
                include $lang_file;
            }
        }
    } else {
        // if the sub-languages feature is not on, then just load the
        // set language interface
        foreach ($language_files as $index => $language_file) {
            // include English
            include $langpath.'english/'.$language_file.'.inc.php';
            // prepare string for current language
            $langfile = $langpath.$language_interface.'/'.$language_file.'.inc.php';
            if (file_exists($langfile)) {
                include $langfile;
            }
        }
    }
}

// The global variable $charset has been defined in a language file too (trad4all.inc.php), this is legacy situation.
// So, we have to reassign this variable again in order to keep its value right.
$charset = $charset_initial_value;

// The global variable $text_dir has been defined in the language file trad4all.inc.php.
// For determing text direction correspondent to the current language we use now information from the internationalization library.
$text_dir = api_get_text_direction();

//Update of the logout_date field in the table track_e_login (needed for the calculation of the total connection time)

if (!isset($_SESSION['login_as']) && isset($_user)) {
    // if $_SESSION['login_as'] is set, then the user is an admin logged as the user

    $tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    $sql_last_connection = "SELECT login_id, login_date FROM $tbl_track_login WHERE login_user_id='".$_user["user_id"]."' ORDER BY login_date DESC LIMIT 0,1";

    $q_last_connection = Database::query($sql_last_connection);
    if (Database::num_rows($q_last_connection) > 0) {
        $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');

        // is the latest logout_date still relevant?
        $sql_logout_date = "SELECT logout_date FROM $tbl_track_login WHERE login_id=$i_id_last_connection";
        $q_logout_date = Database::query($sql_logout_date);
        $res_logout_date = convert_sql_date(Database::result($q_logout_date,0,'logout_date'));

        if ($res_logout_date < time() - $_configuration['session_lifetime']) {
            // it isn't, we should create a fresh entry
            event_login();
            // now that it's created, we can get its ID and carry on
            $q_last_connection = Database::query($sql_last_connection);
            $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');
        }

        $s_sql_update_logout_date = "UPDATE $tbl_track_login SET logout_date=NOW() WHERE login_id='$i_id_last_connection'";
        Database::query($s_sql_update_logout_date);
    }
}