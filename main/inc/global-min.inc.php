<?php
/* For licensing terms, see /license.txt */
/**
 * This is a minified version of global.inc.php meant *only* for download.php
 * to check permissions and deliver the file.
 */

// Include the libraries that are necessary everywhere
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../app/AppKernel.php';

$kernel = new AppKernel('', '');

// Determine the directory path where this current file lies.
// This path will be useful to include the other initialisation files.
$includePath = __DIR__;

// Include the main Chamilo platform configuration file.

$_configuration = [];
$alreadyInstalled = false;
if (file_exists($kernel->getConfigurationFile())) {
    require_once $kernel->getConfigurationFile();
    $alreadyInstalled = true;
    // Recalculate a system absolute path symlinks insensible.
    $includePath = $_configuration['root_sys'].'main/inc/';
} else {
    //Redirects to the main/install/ page
    if (!$alreadyInstalled) {
        $global_error_code = 2;
        // The system has not been installed yet.
        require_once __DIR__.'/../inc/global_error_message.inc.php';
        exit();
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

// Fix bug in IIS that doesn't fill the $_SERVER['REQUEST_URI'].
api_request_uri();

// Do not over-use this variable. It is only for this script's local use.
$libraryPath = __DIR__.'/lib/';

// @todo convert this libs in classes
require_once $libraryPath.'database.constants.inc.php';
require_once $libraryPath.'text.lib.php';
require_once $libraryPath.'array.lib.php';
require_once $libraryPath.'online.inc.php';
require_once $libraryPath.'banner.lib.php';

// Doctrine ORM configuration

$dbParams = [
    'driver' => 'pdo_mysql',
    'host' => $_configuration['db_host'],
    'user' => $_configuration['db_user'],
    'password' => $_configuration['db_password'],
    'dbname' => $_configuration['main_database'],
    // Only relevant for pdo_sqlite, specifies the path to the SQLite database.
    'path' => isset($_configuration['db_path']) ? $_configuration['db_path'] : '',
    // Only relevant for pdo_mysql, pdo_pgsql, and pdo_oci/oci8,
    'port' => isset($_configuration['db_port']) ? $_configuration['db_port'] : '',
];

try {
    $database = new \Database();
    $database->connect($dbParams);
} catch (Exception $e) {
    $global_error_code = 3;
    // The database server is not available or credentials are invalid.
    require $includePath.'/global_error_message.inc.php';
    exit();
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
            $request_url_root = $protocol.'localhost/';
        } else {
            $request_url_root = $protocol.$_SERVER['SERVER_NAME'].'/';
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

// Check if APCu is available. If so, store the value in $_configuration
if (extension_loaded('apcu')) {
    $apcEnabled = ini_get('apc.enabled');
    if (!empty($apcEnabled) && $apcEnabled != 'Off' && $apcEnabled != 'off') {
        $_configuration['apc'] = true;
        $_configuration['apc_prefix'] = $_configuration['main_database'].'_'.$_configuration['access_url'].'_';
    }
}

$charset = 'UTF-8';

// Enables the portability layer and configures PHP for UTF-8
\Patchwork\Utf8\Bootup::initAll();

// Start session after the internationalization library has been initialized.
ChamiloSession::start($alreadyInstalled);

// access_url == 1 is the default chamilo location
if ($_configuration['access_url'] != 1) {
    $url_info = api_get_access_url($_configuration['access_url']);
    if ($url_info['active'] == 1) {
        $settings_by_access = api_get_settings(null, 'list', $_configuration['access_url'], 1);
        foreach ($settings_by_access as &$row) {
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

$result = api_get_settings(null, 'list', 1);
foreach ($result as &$row) {
    if ($_configuration['access_url'] != 1) {
        if ($url_info['active'] == 1) {
            $var = empty($row['variable']) ? 0 : $row['variable'];
            $subkey = empty($row['subkey']) ? 0 : $row['subkey'];
            $category = empty($row['category']) ? 0 : $row['category'];
        }

        if ($row['access_url_changeable'] == 1 && $url_info['active'] == 1) {
            if (isset($settings_by_access_list[$var]) &&
                isset($settings_by_access_list[$var][$subkey]) &&
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

$result = api_get_settings('Plugins', 'list', $_configuration['access_url']);
$_plugins = [];
foreach ($result as &$row) {
    $key = &$row['variable'];
    if (isset($_setting[$key]) && is_string($_setting[$key])) {
        $_setting[$key] = [];
    }
    if ($row['subkey'] == null) {
        $_setting[$key][] = $row['selected_value'];
        $_plugins[$key][] = $row['selected_value'];
    } else {
        $_setting[$key][$row['subkey']] = $row['selected_value'];
        $_plugins[$key][$row['subkey']] = $row['selected_value'];
    }
}

ini_set('log_errors', '1');

/**
 * Include the trad4all language file.
 */
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
    $user_language = preg_replace('/index\.php\?language=/', '', $_POST['language_list']);
}

if (empty($user_language) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !isset($_SESSION['_user'])) {
    $l = SubLanguageManager::getLanguageFromBrowserPreference($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    if (!empty($l)) {
        $user_language = $browser_language = $l;
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
        $language_interface = api_get_language_from_type($language_priority1);
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

$language_interface_initial_value = $language_interface;

$langPath = api_get_path(SYS_LANG_PATH);
$languageFilesToLoad = [
    $langPath.'english/trad4all.inc.php',
    $langPath.$language_interface.'/trad4all.inc.php',
];

foreach ($languageFilesToLoad as $languageFile) {
    if (is_file($languageFile)) {
        require $languageFile;
    }
}

// include the local (contextual) parameters of this course or section
require $includePath.'/local.inc.php';

// Update of the logout_date field in the table track_e_login
// (needed for the calculation of the total connection time)
if (!isset($_SESSION['login_as']) && isset($_user) && isset($_user["user_id"])) {
    // if $_SESSION['login_as'] is set, then the user is an admin logged as the user
    Tracking::updateUserLastLogin($_user["user_id"]);
}
