<?php
/* For licensing terms, see /license.txt */

/**
 * It is recommended that ALL Chamilo scripts include this important file.
 * This script manages
 * - include of /app/config/configuration.php;
 * - include of several libraries: api, database, display, text, security;
 * - selecting the main database;
 * - include of language files.
 *
 * @package chamilo.include
 * @todo remove the code that displays the button that links to the install page
 * 		but use a redirect immediately. By doing so the $alreadyInstalled variable can be removed.
 *
 */

// Showing/hiding error codes in global error messages.
define('SHOW_ERROR_CODES', false);

// Include the libraries that are necessary everywhere
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../app/AppKernel.php';

$kernel = new AppKernel('', '');

// Determine the directory path where this current file lies.
// This path will be useful to include the other initialisation files.
$includePath = __DIR__;

// Include the main Chamilo platform configuration file.

$alreadyInstalled = false;
if (file_exists($kernel->getConfigurationFile())) {
    require_once $kernel->getConfigurationFile();
    $alreadyInstalled = true;
    // Recalculate a system absolute path symlinks insensible.
    $includePath = $_configuration['root_sys'].'main/inc/';
} else {
    $_configuration = array();
    //Redirects to the main/install/ page
    if (!$alreadyInstalled) {
        $global_error_code = 2;
        // The system has not been installed yet.
        require_once __DIR__ . '/../inc/global_error_message.inc.php';
        die();
    }
}

$kernel->setApi($_configuration);

// Ensure that _configuration is in the global scope before loading
// main_api.lib.php. This is particularly helpful for unit tests
if (!isset($GLOBALS['_configuration'])) {
    $GLOBALS['_configuration'] = $_configuration;
}

// Include the main Chamilo platform library file.

require_once $_configuration['root_sys'].'main/inc/lib/api.lib.php';
$passwordEncryption = api_get_configuration_value('password_encryption');

if ($passwordEncryption === 'bcrypt') {
    require_once __DIR__.'/../../vendor/ircmaxell/password-compat/lib/password.php';
}

// Check the PHP version
api_check_php_version($includePath.'/');

// Specification for usernames:
// 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 40 characters maximum length.
// 2. Empty username is formally valid, but it is reserved for the anonymous user.
// 3. Checking the login_is_email portal setting in order to accept 100 chars maximum

$defaultUserNameLength = 40;
if (api_get_setting('login_is_email') == 'true') {
    $defaultUserNameLength = 100;
}
define('USERNAME_MAX_LENGTH', $defaultUserNameLength);

// Fix bug in IIS that doesn't fill the $_SERVER['REQUEST_URI'].
api_request_uri();

define('_MPDF_TEMP_PATH', __DIR__.'/../../app/cache/mpdf/');
define('_MPDF_TTFONTDATAPATH', __DIR__.'/../../app/cache/mpdf/');

// Include the libraries that are necessary everywhere
require_once __DIR__.'/../../vendor/autoload.php';

// Do not over-use this variable. It is only for this script's local use.
$libraryPath = __DIR__.'/lib/';

// @todo convert this libs in classes
require_once $libraryPath.'database.constants.inc.php';
require_once $libraryPath.'text.lib.php';
require_once $libraryPath.'array.lib.php';
require_once $libraryPath.'online.inc.php';
require_once $libraryPath.'banner.lib.php';
require_once $libraryPath.'fileManage.lib.php';
require_once $libraryPath.'fileUpload.lib.php';
require_once $libraryPath.'fileDisplay.lib.php';
require_once $libraryPath.'course_category.lib.php';

if (!is_dir(_MPDF_TEMP_PATH)) {
    mkdir(_MPDF_TEMP_PATH, api_get_permissions_for_new_directories(), true);
}

// Connect to the server database and select the main chamilo database.
// When $_configuration['db_persistent_connection'] is set, it is expected to be a boolean type.
/*$dbPersistConnection = api_get_configuration_value('db_persistent_connection');
// $_configuration['db_client_flags'] can be set in configuration.php to pass
// flags to the DB connection
$dbFlags = api_get_configuration_value('db_client_flags');

$params = array(
    'server' => $_configuration['db_host'],
    'username' => $_configuration['db_user'],
    'password' => $_configuration['db_password'],
    'persistent' => $dbPersistConnection,
    'client_flags' => $dbFlags,
);*/

// Doctrine ORM configuration

$dbParams = array(
    'driver' => 'pdo_mysql',
    'host' => $_configuration['db_host'],
    'user' => $_configuration['db_user'],
    'password' => $_configuration['db_password'],
    'dbname' => $_configuration['main_database'],
    // Only relevant for pdo_sqlite, specifies the path to the SQLite database.
    'path' => isset($_configuration['db_path']) ? $_configuration['db_path'] : '',
    // Only relevant for pdo_mysql, pdo_pgsql, and pdo_oci/oci8,
    'port' => isset($_configuration['db_port']) ? $_configuration['db_port'] : ''
);

try {
    $database = new \Database();
    $database->connect($dbParams);
} catch (Exception $e) {
    $global_error_code = 3;
    // The database server is not available or credentials are invalid.
    require $includePath.'/global_error_message.inc.php';
    die();
}

/* RETRIEVING ALL THE CHAMILO CONFIG SETTINGS FOR MULTIPLE URLs FEATURE*/
if (!empty($_configuration['multiple_access_urls'])) {
    $_configuration['access_url'] = 1;
    $access_urls = api_get_access_urls();
    $root_rel = api_get_self();
    $root_rel = substr($root_rel, 1);
    $pos = strpos($root_rel, '/');
    $root_rel = substr($root_rel, 0, $pos);
    $protocol = 'http://';
    if (!empty($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) != 'OFF') {
        $protocol = 'https://';
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $protocol = 'https://';
    }

    //urls with subdomains (HTTP_HOST is preferred - see #6764)
    $request_url_root = '';
    if (empty($_SERVER['HTTP_HOST'])) {
        if (empty($_SERVER['SERVER_NAME'])) {
            $request_url_root = $protocol . 'localhost/';
        } else {
            $request_url_root = $protocol . $_SERVER['SERVER_NAME'] . '/';
        }
    } else {
        $request_url_root = $protocol.$_SERVER['HTTP_HOST'].'/';
    }
    //urls with subdirs
    $request_url_sub = $request_url_root.$root_rel.'/';

    // You can use subdirs as multi-urls, but in this case none of them can be
    // the root dir. The admin portal should be something like https://host/adm/
    // At this time, subdirs will still hold a share cookie, so not ideal yet
    // see #6510
    foreach ($access_urls as $details) {
        if ($request_url_sub == $details['url']) {
            $_configuration['access_url'] = $details['id'];
            break; //found one match with subdir, get out of foreach
        }
        // Didn't find any? Now try without subdirs
        if ($request_url_root == $details['url']) {
            $_configuration['access_url'] = $details['id'];
            break; //found one match, get out of foreach
        }
    }
} else {
    $_configuration['access_url'] = 1;
}

$charset = 'UTF-8';

// Enables the portablity layer and configures PHP for UTF-8
\Patchwork\Utf8\Bootup::initAll();

// Start session after the internationalization library has been initialized.
ChamiloSession::instance()->start($alreadyInstalled);

// Remove quotes added by PHP  - get_magic_quotes_gpc() is deprecated in PHP 5 see #2970

if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    array_walk_recursive_limited($_GET, 'stripslashes', true);
    array_walk_recursive_limited($_POST, 'stripslashes', true);
    array_walk_recursive_limited($_COOKIE, 'stripslashes', true);
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
            if (isset($settings_by_access_list[$var]) &&
                $settings_by_access_list[$var][$subkey][$category]['selected_value'] != '') {
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

// Error reporting settings.
if (api_get_setting('server_type') == 'test') {
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    error_reporting(-1);
} else {
    error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);
}

// Load allowed tag definitions for kses and/or HTMLPurifier.
require_once $libraryPath.'formvalidator/Rule/allowed_tags.inc.php';

// Before we call local.inc.php, let's define a global $this_section variable
// which will then be usable from the banner and header scripts
$this_section = SECTION_GLOBAL;

// Include Chamilo Mail conf this is added here because the api_get_setting works

// Fixes bug in Chamilo 1.8.7.1 array was not set
$administrator['email'] = isset($administrator['email']) ? $administrator['email'] : 'admin@example.com';
$administrator['name'] = isset($administrator['name']) ? $administrator['name'] : 'Admin';

// Including configuration files
$configurationFiles = array(
    'mail.conf.php',
    'profile.conf.php',
    'course_info.conf.php',
    'add_course.conf.php',
    'events.conf.php',
    'auth.conf.php',
    'portfolio.conf.php'
);

foreach ($configurationFiles as $file) {
    $file = api_get_path(CONFIGURATION_PATH).$file;
    if (file_exists($file)) {
        require_once $file;
    }
}


/*  LOAD LANGUAGE FILES SECTION */

// if we use the javascript version (without go button) we receive a get
// if we use the non-javascript version (with the go button) we receive a post
$user_language = '';
$browser_language = '';

// see #8149
if (!empty($_SESSION['user_language_choice'])) {
    $user_language = $_SESSION['user_language_choice'];
}

if (!empty($_GET['language'])) {
    $user_language = $_GET['language'];
}

if (!empty($_POST['language_list'])) {
    $user_language = str_replace('index.php?language=', '', $_POST['language_list']);
}

if (empty($user_language) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_SESSION['_user'])) {
    $l = SubLanguageManager::getLanguageFromBrowserPreference($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (!empty($l)) {
        $user_language = $browser_language = $l;
    }
}

// Include all files (first english and then current interface language)
$langpath = api_get_path(SYS_LANG_PATH);

/* This will only work if we are in the page to edit a sub_language */
if (isset($this_script) && $this_script == 'sub_language') {
    // getting the arrays of files i.e notification, trad4all, etc
    $language_files_to_load = SubLanguageManager:: get_lang_folder_files_list(
        api_get_path(SYS_LANG_PATH).'english',
        true
    );
    //getting parent info
    $parent_language = SubLanguageManager::get_all_information_of_language($_REQUEST['id']);
    //getting sub language info
    $sub_language = SubLanguageManager::get_all_information_of_language($_REQUEST['sub_language_id']);

    $english_language_array = $parent_language_array = $sub_language_array = array();

    foreach ($language_files_to_load as $language_file_item) {
        $lang_list_pre = array_keys($GLOBALS);
        //loading english
        $path = $langpath.'english/'.$language_file_item.'.inc.php';
        if (file_exists($path)) {
            include $path;
        }

        $lang_list_post = array_keys($GLOBALS);
        $lang_list_result = array_diff($lang_list_post, $lang_list_pre);
        unset($lang_list_pre);

        //  english language array
        $english_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach ($lang_list_result as $item) {
            unset(${$item});
        }
        $parent_file = $langpath.$parent_language['dokeos_folder'].'/'.$language_file_item.'.inc.php';

        if (file_exists($parent_file) && is_file($parent_file)) {
            include_once $parent_file;
        }
        //  parent language array
        $parent_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach ($lang_list_result as $item) {
            unset(${$item});
        }

        $sub_file = $langpath.$sub_language['dokeos_folder'].'/'.$language_file_item.'.inc.php';
        if (file_exists($sub_file) && is_file($sub_file)) {
            include $sub_file;
        }

        //  sub language array
        $sub_language_array[$language_file_item] = compact($lang_list_result);

        //cleaning the variables
        foreach ($lang_list_result as $item) {
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

    if (isset($_GET['language']) ||
        (isset($_POST['language_list']) && !empty($_POST['language_list'])) ||
        !empty($browser_language)
    ) {
        $user_selected_language = $user_language; // $_GET['language']; or HTTP_ACCEPT_LANGUAGE
        $_SESSION['user_language_choice'] = $user_selected_language;
        $platformLanguage = $user_selected_language;
    }

    if (!empty($language_priority4) && api_get_language_from_type($language_priority4) !== false) {
        $language_interface = api_get_language_from_type($language_priority4);
    } else {
        $language_interface = api_get_setting('platformLanguage');
    }

    if (!empty($language_priority3) && api_get_language_from_type($language_priority3) !== false) {
        $language_interface = api_get_language_from_type($language_priority3);
    } else {
        if (isset($_SESSION['user_language_choice'])) {
            $language_interface = $_SESSION['user_language_choice'];
        }
    }

    if (!empty($language_priority2) && api_get_language_from_type($language_priority2) !== false) {
        $language_interface = api_get_language_from_type($language_priority2);
    } else {
        if (isset($_user['language'])) {
            $language_interface = $_user['language'];
        }
    }

    if (!empty($language_priority1) && api_get_language_from_type($language_priority1) !== false) {
        $language_interface =  api_get_language_from_type($language_priority1);
    } else {
        if (isset($_course['language'])) {
            $language_interface = $_course['language'];
        }
    }

    // If language is set via browser ignore the priority
    if (isset($_GET['language'])) {
        $language_interface = $user_language;
    }
}

// Sometimes the variable $language_interface is changed
// temporarily for achieving translation in different language.
// We need to save the genuine value of this variable and
// to use it within the function get_lang(...).
$language_interface_initial_value = $language_interface;

/**
 * Include the trad4all language file
 */
// if the sub-language feature is on
$parent_path = SubLanguageManager::get_parent_language_path($language_interface);
if (!empty($parent_path)) {
    // include English
    include $langpath.'english/trad4all.inc.php';
    // prepare string for current language and its parent
    $lang_file = $langpath.$language_interface.'/trad4all.inc.php';
    $parent_lang_file = $langpath.$parent_path.'/trad4all.inc.php';
    // load the parent language file first
    if (file_exists($parent_lang_file)) {
        include $parent_lang_file;
    }
    // overwrite the parent language translations if there is a child
    if (file_exists($lang_file)) {
        include $lang_file;
    }
} else {
    // if the sub-languages feature is not on, then just load the
    // set language interface
    // include English
    include $langpath.'english/trad4all.inc.php';
    // prepare string for current language
    $langfile = $langpath.$language_interface.'/trad4all.inc.php';

    if (file_exists($langfile)) {
        include $langfile;
    }
}

// include the local (contextual) parameters of this course or section
require $includePath.'/local.inc.php';

// The global variable $text_dir has been defined in the language file trad4all.inc.php.
// For determining text direction correspondent to the current language
// we use now information from the internationalization library.
$text_dir = api_get_text_direction();

// ===== "who is logged in?" module section =====

// check and modify the date of user in the track.e.online table
if (!$x = strpos($_SERVER['PHP_SELF'], 'whoisonline.php')) {
    preventMultipleLogin($_user["user_id"]);
    LoginCheck(isset($_user['user_id']) ? $_user['user_id'] : '');
}

// ===== end "who is logged in?" module section =====

//Update of the logout_date field in the table track_e_login
// (needed for the calculation of the total connection time)

if (!isset($_SESSION['login_as']) && isset($_user)) {
    // if $_SESSION['login_as'] is set, then the user is an admin logged as the user

    $tbl_track_login = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
    $sql = "SELECT login_id, login_date
            FROM $tbl_track_login
            WHERE
                login_user_id='".$_user["user_id"]."'
            ORDER BY login_date DESC
            LIMIT 0,1";

    $q_last_connection = Database::query($sql);
    if (Database::num_rows($q_last_connection) > 0) {
        $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');

        // is the latest logout_date still relevant?
        $sql = "SELECT logout_date FROM $tbl_track_login
                WHERE login_id = $i_id_last_connection";
        $q_logout_date = Database::query($sql);
        $res_logout_date = convert_sql_date(Database::result($q_logout_date, 0, 'logout_date'));

        if ($res_logout_date < time() - $_configuration['session_lifetime']) {
            // it isn't, we should create a fresh entry
            Event::event_login($_user['user_id']);
            // now that it's created, we can get its ID and carry on
            $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');
        }
        $now = api_get_utc_datetime(time());
        $sql = "UPDATE $tbl_track_login SET logout_date = '$now'
                WHERE login_id='$i_id_last_connection'";
        Database::query($sql);

        $tableUser = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "UPDATE $tableUser SET last_login = '$now'
                WHERE user_id = ".$_user["user_id"];
        Database::query($sql);
    }
}

// Add language_measure_frequency to your main/inc/conf/configuration.php in
// order to generate language variables frequency measurements (you can then
// see them through main/cron/lang/langstats.php)
// The langstat object will then be used in the get_lang() function.
// This block can be removed to speed things up a bit as it should only ever
// be used in development versions.
if (isset($_configuration['language_measure_frequency']) &&
    $_configuration['language_measure_frequency'] == 1
) {
    require_once api_get_path(SYS_CODE_PATH).'/cron/lang/langstats.class.php';
    $langstats = new langstats();
}

//Default quota for the course documents folder
$default_quota = api_get_setting('default_document_quotum');
//Just in case the setting is not correctly set
if (empty($default_quota)) {
    $default_quota = 100000000;
}
define('DEFAULT_DOCUMENT_QUOTA', $default_quota);

// Sets the ascii_math plugin see #7134
$_SESSION['ascii_math_loaded'] = false;

