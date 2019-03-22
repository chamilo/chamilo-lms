<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\Dotenv\Dotenv;

/**
 * It is recommended that ALL Chamilo scripts include this important file.
 * This script manages
 * - include of /app/config/configuration.php;
 * - include of several libraries: api, database, display, text, security;
 * - selecting the main database;
 * - include of language files.
 *
 * @package chamilo.include
 *
 * @todo remove the code that displays the button that links to the install page
 */

// Showing/hiding error codes in global error messages.
define('SHOW_ERROR_CODES', false);

// Specification for usernames:
// 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 40 characters maximum length.
// 2. Empty username is formally valid, but it is reserved for the anonymous user.
// 3. Checking the login_is_email portal setting in order to accept 100 chars maximum
define('USERNAME_MAX_LENGTH', 100);
//define('_MPDF_TEMP_PATH', __DIR__.'/../../var/cache/mpdf/');
//define('_MPDF_TTFONTDATAPATH', __DIR__.'/../../var/cache/mpdf/');

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../public/legacy.php';

// Check the PHP version
api_check_php_version(__DIR__.'/');

try {
    // Get settings from .env file created when installation Chamilo
    $envFile = __DIR__.'/../../.env';
    if (file_exists($envFile)) {
        (new Dotenv())->load($envFile);
    } else {
        throw new \RuntimeException('APP_ENV environment variable is not defined.
        You need to define environment variables for configuration to load variables from a .env file.');
    }

    $env = $_SERVER['APP_ENV'] ?? 'dev';
    $append = $_SERVER['APP_URL_APPEND'] ?? '';

    $kernel = new Chamilo\Kernel($env, true);
    $request = Sonata\PageBundle\Request\RequestFactory::createFromGlobals('host_with_path_by_locale');

    // This 'load_legacy' variable is needed to know that symfony is loaded using old style legacy mode,
    // and not called from a symfony controller from public/
    $request->request->set('load_legacy', true);

    // @todo fix URL loading
    $request->setBaseUrl($request->getRequestUri());
    $kernel->boot();
    if (!empty($append)) {
        if (substr($append, 0, 1) !== '/') {
            echo 'APP_URL_APPEND must start with "/"';
            exit;
        }
        $append = "$append/";
    }

    $container = $kernel->getContainer();

    $router = $container->get('router');
    $context = $router->getContext();
    $context->setBaseUrl($append);
    $router->setContext($context);
    $response = $kernel->handle($request);
    $context = Container::getRouter()->getContext();
    $context->setBaseUrl($append);
    $container = $kernel->getContainer();

    if ($kernel->isInstalled()) {
        require_once $kernel->getConfigurationFile();
    } else {
        $_configuration = [];
        // Redirects to the main/install/ page
        $global_error_code = 2;
        // The system has not been installed yet.
        require_once __DIR__.'/../inc/global_error_message.inc.php';
        exit;
    }

    $kernel->setApi($_configuration);

    // Ensure that _configuration is in the global scope before loading
    // main_api.lib.php. This is particularly helpful for unit tests
    if (!isset($GLOBALS['_configuration'])) {
        $GLOBALS['_configuration'] = $_configuration;
    }

    // Do not over-use this variable. It is only for this script's local use.
    $libraryPath = __DIR__.'/lib/';
    $container = $kernel->getContainer();

    // Symfony uses request_stack now
    $container->get('request_stack')->push($request);

    // Connect Chamilo with the Symfony container
    // Container::setContainer($container);
    // Container::setLegacyServices($container);

    // The code below is not needed. The connections is now made in the file:
    // src/CoreBundle/EventListener/LegacyListener.php
    // This is called when when doing the $kernel->handle

    // Fix chamilo URL when used inside a folder: example.com/chamilo
    /*$append = $kernel->getUrlAppend();
    $appendValue = '';
    if (!empty($append)) {
        $appendValue = "/$append/";
    }*/
    /*$router = $container->get('router');
    $context = $container->get('router.request_context');
    $host = $router->getContext()->getHost();
    $context->setBaseUrl($appendValue);
    $container->set('router.request_context', $context);*/

    /*$version = new Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy();
    $newDefault = new Symfony\Component\Asset\PathPackage($appendValue.'public/', $version);
    $packages = $container->get('assets.packages');
    $packages->setDefaultPackage($newDefault);
    $container->get('chamilo_core.menu.nav_builder')->setContainer($container);*/

    /*if (!is_dir(_MPDF_TEMP_PATH)) {
        mkdir(_MPDF_TEMP_PATH, api_get_permissions_for_new_directories(), true);
    }*/

    /* RETRIEVING ALL THE CHAMILO CONFIG SETTINGS FOR MULTIPLE URLs FEATURE*/
    /*if (!empty($_configuration['multiple_access_urls'])) {
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
    }*/

    // Check if APCu is available. If so, store the value in $_configuration
    /*if (extension_loaded('apcu')) {
        $apcEnabled = ini_get('apc.enabled');
        if (!empty($apcEnabled) && $apcEnabled != 'Off' && $apcEnabled != 'off') {
            $_configuration['apc'] = true;
            $_configuration['apc_prefix'] = $_configuration['main_database'].'_'.$_configuration['access_url'].'_';
        }
    }*/

    $charset = 'UTF-8';

    // Enables the portability layer and configures PHP for UTF-8
    \Patchwork\Utf8\Bootup::initAll();

    // access_url == 1 is the default chamilo location
    /*if ($_configuration['access_url'] != 1) {
        $url_info = api_get_access_url($_configuration['access_url']);
        if ($url_info['active'] == 1) {
            $settings_by_access = &api_get_settings(null, 'list', $_configuration['access_url'], 1);
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
    }*/

    /*$result = &api_get_settings(null, 'list', 1);
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
    }*/

    /*$result = &api_get_settings('Plugins', 'list', $_configuration['access_url']);
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
    }*/

    // Error reporting settings.
    /*if (api_get_setting('server_type') === 'test') {
        ini_set('display_errors', '1');
        ini_set('html_errors', '1');
        error_reporting(-1);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    } else {
        error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);
    }*/

    ini_set('log_errors', '1');

    // Load allowed tag definitions for kses and/or HTMLPurifier.
    //require_once $libraryPath.'formvalidator/Rule/allowed_tags.inc.php';

    // Before we call local.inc.php, let's define a global $this_section variable
    // which will then be usable from the banner and header scripts
    $this_section = SECTION_GLOBAL;

    // Include Chamilo Mail conf this is added here because the api_get_setting works

    // Fixes bug in Chamilo 1.8.7.1 array was not set
    //$administrator['email'] = isset($administrator['email']) ? $administrator['email'] : 'admin@example.com';
    //$administrator['name'] = isset($administrator['name']) ? $administrator['name'] : 'Admin';

    /*  LOAD LANGUAGE FILES SECTION */
    // if we use the javascript version (without go button) we receive a get
    // if we use the non-javascript version (with the go button) we receive a post
    /*$user_language = '';
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
    }*/

    // Include all files (first english and then current interface language)
    /*$langpath = api_get_path(SYS_LANG_PATH);

    // This will only work if we are in the page to edit a sub_language
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

        $english_language_array = $parent_language_array = $sub_language_array = [];

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
    }*/

    /**
     * Include the trad4all language file.
     */
    // if the sub-language feature is on
    /*$parent_path = SubLanguageManager::get_parent_language_path($language_interface);
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
    }*/

    // include the local (contextual) parameters of this course or section
    //require_once __DIR__.'/local.inc.php';
    //$_user = api_get_user_info();

    // The global variable $text_dir has been defined in the language file trad4all.inc.php.
    // For determining text direction correspondent to the current language
    // we use now information from the internationalization library.
    //$text_dir = api_get_text_direction();

    // ===== "who is logged in?" module section =====

    // check and modify the date of user in the track.e.online table
    /*if (isset($_user['user_id'])) {
        if (!$x = strpos($_SERVER['PHP_SELF'], 'whoisonline.php')) {
            preventMultipleLogin($_user['user_id']);
            LoginCheck($_user['user_id']);
        }

        // Update of the logout_date field in the table track_e_login
        // (needed for the calculation of the total connection time)
        if (!isset($_SESSION['login_as'])) {
            // if $_SESSION['login_as'] is set, then the user is an admin logged as the user
            $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
            $sql = "SELECT login_id, login_date
                    FROM $tbl_track_login
                    WHERE
                        login_user_id='".$_user["user_id"]."'
                    ORDER BY login_date DESC
                    LIMIT 0,1";

            $q_last_connection = Database::query($sql);
            if (Database::num_rows($q_last_connection) > 0) {
                $now = api_get_utc_datetime();
                $i_id_last_connection = Database::result($q_last_connection, 0, 'login_id');

                // is the latest logout_date still relevant?
                $sql = "SELECT logout_date FROM $tbl_track_login
                        WHERE login_id = $i_id_last_connection";
                $q_logout_date = Database::query($sql);
                $res_logout_date = convert_sql_date(Database::result($q_logout_date, 0, 'logout_date'));
                $lifeTime = api_get_configuration_value('session_lifetime');

                if ($res_logout_date < time() - $lifeTime) {
                    // it isn't, we should create a fresh entry
                    // now that it's created, we can get its ID and carry on
                    Event::eventLogin($_user['user_id']);
                } else {
                    $sql = "UPDATE $tbl_track_login SET logout_date = '$now'
                            WHERE login_id = '$i_id_last_connection'";
                    Database::query($sql);
                }

                $tableUser = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "UPDATE $tableUser SET last_login = '$now'
                        WHERE user_id = ".$_user["user_id"];
                Database::query($sql);
            }
        }
    }*/

    // Add language_measure_frequency to your main/inc/conf/configuration.php in
    // order to generate language variables frequency measurements (you can then
    // see them through main/cron/lang/langstats.php)
    // The langstat object will then be used in the get_lang() function.
    // This block can be removed to speed things up a bit as it should only ever
    // be used in development versions.
    if (isset($_configuration['language_measure_frequency']) &&
        $_configuration['language_measure_frequency'] == 1
    ) {
        //require_once api_get_path(SYS_CODE_PATH).'/cron/lang/langstats.class.php';
        //$langstats = new langstats();
    }

    //Default quota for the course documents folder
    /*$default_quota = api_get_setting('default_document_quotum');
    //Just in case the setting is not correctly set
    if (empty($default_quota)) {
        $default_quota = 100000000;
    }
    define('DEFAULT_DOCUMENT_QUOTA', $default_quota);*/
    // Forcing PclZip library to use a custom temporary folder.
    //define('PCLZIP_TEMPORARY_DIR', api_get_path(SYS_ARCHIVE_PATH));
} catch (Exception $e) {
    error_log($e->getMessage()); /*
    var_dump($e->getMessage());
    var_dump($e->getCode());
    var_dump($e->getLine());
    echo $e->getTraceAsString();
    exit;*/
}
