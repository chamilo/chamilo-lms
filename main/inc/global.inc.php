<?php

/** For licensing terms, see /license.txt */

/**
 * This is a bootstrap file that loads all Chamilo dependencies including:
 *
 * - Chamilo settings config/configuration.yml or config/configuration.php (in this order, using what if finds first)
 * - Database (Using Doctrine DBAL/ORM)
 * - Templates (Using Twig)
 * - Loading language files (Using Symfony component)
 * - Loading mail settings (Using SwiftMailer smtp/sendmail/mail)
 * - Debug (Using Monolog)
 *
 * ALL Chamilo scripts must include this file in order to have the $app container
 * This script returns a $app Application instance so you have access to all the services.
 *
 * @package chamilo.include
 *
 */

use Silex\Application;
use \ChamiloSession as Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\Loader\PoFileLoader;
//use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Dumper\MoFileDumper;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

use ChamiloLMS\Component\DataFilesystem\DataFilesystem;
use ChamiloLMS\Entity\User;

// Determine the directory path for this file.
$includePath = dirname(__FILE__);

// Start Silex.
$app = new Application();
// @todo add a helper to read the configuration file once!

// Include the main Chamilo platform configuration file.
// @todo use a service provider to load configuration files:
/*
    $app->register(new Igorw\Silex\ConfigServiceProvider($settingsFile));
*/

/** Reading configuration files */
// Reading configuration file from main/inc/conf/configuration.php or app/config/configuration.yml
$configurationFilePath = $includePath.'/conf/configuration.php';
$configurationYMLFile = $includePath.'/../../config/configuration.yml';
$configurationFileAppPath = $includePath.'/../../config/configuration.php';

$alreadyInstalled = false;
$_configuration = array();

if (file_exists($configurationFilePath) || file_exists($configurationYMLFile)  || file_exists($configurationFileAppPath)) {
    if (file_exists($configurationFilePath)) {
        require_once $configurationFilePath;
    }

    if (file_exists($configurationFileAppPath)) {
        $configurationFilePath = $configurationFileAppPath;
        require_once $configurationFileAppPath;
    }
    $alreadyInstalled = true;
}

// Overwriting $_configuration
if (file_exists($configurationYMLFile)) {
    $yaml = new Parser();
    $configurationYML = $yaml->parse(file_get_contents($configurationYMLFile));
    if (is_array($configurationYML) && !empty($configurationYML)) {
        if (isset($_configuration)) {
            $_configuration = array_merge($_configuration, $configurationYML);
        } else {
            $_configuration = $configurationYML;
        }
    }
}

/** Setting Chamilo paths */

$app['root_sys'] = isset($_configuration['root_sys']) ? $_configuration['root_sys'] : dirname(dirname(__DIR__)).'/';
$app['sys_root'] = $app['root_sys'];
$app['sys_data_path'] = isset($_configuration['sys_data_path']) ? $_configuration['sys_data_path'] : $app['root_sys'].'data/';
$app['sys_config_path'] = isset($_configuration['sys_config_path']) ? $_configuration['sys_config_path'] : $app['root_sys'].'config/';
$app['sys_course_path'] = isset($_configuration['sys_course_path']) ? $_configuration['sys_course_path'] : $app['sys_data_path'].'courses/';
$app['sys_temp_path'] = isset($_configuration['sys_temp_path']) ? $_configuration['sys_temp_path'] : $app['sys_data_path'].'temp/';
$app['sys_log_path'] = isset($_configuration['sys_log_path']) ? $_configuration['sys_log_path'] : $app['root_sys'].'logs/';

/** Loading config files (mail, auth, profile) */

if ($alreadyInstalled) {

    $configPath = $app['sys_config_path'];

    $confFiles = array(
        'auth.conf.php',
        'events.conf.php',
        'mail.conf.php',
        'portfolio.conf.php',
        'profile.conf.php'
    );

    foreach ($confFiles as $confFile) {
        if (file_exists($configPath.$confFile)) {
            require_once $configPath.$confFile;
        }
    }

    // Fixing $_configuration array

    // Fixes bug in Chamilo 1.8.7.1 array was not set
    $administrator['email'] = isset($administrator['email']) ? $administrator['email'] : 'admin@example.com';
    $administrator['name'] = isset($administrator['name']) ? $administrator['name'] : 'Admin';

    // Code for transitional purposes, it can be removed right before the 1.8.7 release.
    /*if (empty($_configuration['system_version'])) {
        $_configuration['system_version'] = (!empty($_configuration['dokeos_version']) ? $_configuration['dokeos_version'] : '');
        $_configuration['system_stable'] = (!empty($_configuration['dokeos_stable']) ? $_configuration['dokeos_stable'] : '');
        $_configuration['software_url'] = 'http://www.chamilo.org/';
    }*/

    // For backward compatibility.
    $_configuration['dokeos_version'] = isset($_configuration['system_version']) ? $_configuration['system_version'] : null;
    //$_configuration['dokeos_stable'] = $_configuration['system_stable'];
    $userPasswordCrypted = (!empty($_configuration['password_encryption']) ? $_configuration['password_encryption'] : 'sha1');
}

/** End loading config files */

/** Including legacy libs */
require_once $includePath.'/lib/api.lib.php';

// Setting $_configuration['url_append']
$urlInfo = isset($_configuration['root_web']) ? parse_url($_configuration['root_web']) : null;
$_configuration['url_append'] = null;
if (isset($urlInfo['path'])) {
    $_configuration['url_append'] = '/'.basename($urlInfo['path']);
}

$libPath = $includePath.'/lib/';

// Database constants
require_once $libPath.'database.constants.inc.php';

// @todo Rewrite the events.lib.inc.php in a class
require_once $libPath.'events.lib.inc.php';

// Load allowed tag definitions for kses and/or HTMLPurifier.
require_once $libPath.'formvalidator/Rule/allowed_tags.inc.php';

// Add the path to the pear packages to the include path
ini_set('include_path', api_create_include_path_setting($includePath));

$app['configuration_file'] = $configurationFilePath;
$app['configuration_yml_file'] = $configurationYMLFile;
$app['languages_file'] = array();
$app['installed'] = $alreadyInstalled;
$app['app.theme'] = 'chamilo';

// Developer options relies in the configuration.php file

$app['debug'] = isset($_configuration['debug']) ? $_configuration['debug'] : false;
$app['show_profiler'] = isset($_configuration['show_profiler']) ? $_configuration['show_profiler'] : false;

// Enables assetic in order to load 1 compressed stylesheet or split files
//$app['assetic.enabled'] = $app['debug'];
// Hardcoded to false by default. Implementation is not finished yet.
$app['assetic.enabled'] = false;

// Dumps assets
$app['assetic.auto_dump_assets'] = false;

// Loading $app settings depending of the debug option
if ($app['debug']) {
    require_once __DIR__.'/../../src/ChamiloLMS/Resources/config/dev.php';
} else {
    require_once __DIR__.'/../../src/ChamiloLMS/Resources/config/prod.php';
}

// Classic way of render pages or the Controller approach
$app['classic_layout'] = false;
$app['full_width'] = false;
$app['breadcrumb'] = array();

// The script is allowed? This setting is modified when calling api_is_not_allowed()
$app['allowed'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());

// Session settings
$app['session.storage.options'] = array(
    'name' => 'chamilo_session',
    //'cookie_lifetime' => 30, //Cookie lifetime
    //'cookie_path' => null, //Cookie path
    //'cookie_domain' => null, //Cookie domain
    //'cookie_secure' => null, //Cookie secure (HTTPS)
    'cookie_httponly' => true //Whether the cookie is http only
);

// Loading chamilo settings
/* @todo create a service provider to load plugins.
   Check how bolt add extensions (including twig templates, config with yml)*/

// Template settings loaded in template.lib.php
$app['template.show_header'] = true;
$app['template.show_footer'] = true;
$app['template.show_learnpath'] = false;
$app['template.hide_global_chat'] = true;
$app['template.load_plugins'] = true;
$app['configuration'] = $_configuration;

// Inclusion of internationalization libraries
require_once $libPath.'internationalization.lib.php';
// Functions for internal use behind this API
require_once $libPath.'internationalization_internal.lib.php';

$_plugins = array();
if ($alreadyInstalled) {
    /** Including service providers */
    require_once 'services.php';
}

$charset = 'UTF-8';

// Preserving the value of the global variable $charset.
$charset_initial_value = $charset;

// Section (tabs in the main Chamilo menu)
$app['this_section'] = SECTION_GLOBAL;

// Manage Chamilo error messages
$app->error(
    function (\Exception $e, $code) use ($app) {
        if ($app['debug']) {
            //return;
        }
        $message = null;
        if (isset($code)) {
            switch ($code) {
                case 401:
                    $message = 'Unauthorized';
                    break;
                case 404: // not found
                    $message = $e->getMessage();
                    if (empty($message)) {
                        $message = 'The requested page could not be found.';
                    }
                    break;
                default:
                    //$message = 'We are sorry, but something went terribly wrong.';
                    $message = $e->getMessage();
            }
        } else {
            $code = null;
        }

        if ($e instanceof PDOException) {
            $message = "There's an error with the database.";
            if ($app['debug']) {
                $message = $e->getMessage();
            }
            return $message;
        }

        Session::setSession($app['session']);

        $templateStyle = api_get_setting('template');
        $templateStyle = isset($templateStyle) && !empty($templateStyle) ? $templateStyle : 'default';

        if (!is_dir($app['sys_root'].'main/template/'.$templateStyle)) {
            $templateStyle = 'default';
        }

        $app['template_style'] = $templateStyle;

        // Default layout.
        $app['default_layout'] = $app['template_style'].'/layout/layout_1_col.tpl';
        /** @var Template $template */
        $template = $app['template'];

        $template->setHeader($app['template.show_header']);
        $template->setFooter($app['template.show_footer']);

        $template->assign('error', array('code' => $code, 'message' => $message));
        $response = $template->renderLayout('error.tpl');

        return new Response($response);
    }
);

// Checking if we have a valid language. If not we set it to the platform language.
$cidReset = null;

/** Silex Middlewares. */

/* A "before" middleware allows you to tweak the Request
 * before the controller is executed. */

$app->before(
    function () use ($app) {
         /** @var Request $request */
        $request = $app['request'];

        // Checking configuration file. If does not exists redirect to the install folder.
        if (!file_exists($app['configuration_file']) && !file_exists($app['configuration_yml_file'])) {
            $url = str_replace('web', 'main/install', $request->getBasePath());
            return new RedirectResponse($url);
        }

        // Check data folder
        if (!is_writable($app['sys_data_path'])) {
            $app->abort(500, "data folder must be writable.");
        }

        // Checks temp folder permissions.
        if (!is_writable($app['sys_temp_path'])) {
            $app->abort(500, "data/temp folder must be writable.");
        }

        // Checking that configuration is loaded
        if (!isset($app['configuration'])) {
            $app->abort(500, '$configuration array must be set in the configuration.php file.');
        }

        $configuration = $app['configuration'];

        // Check if root_web exists
        if (!isset($configuration['root_web'])) {
            $app->abort(500, '$configuration[root_web] must be set in the configuration.php file.');
        }

        // Starting the session for more info see: http://silex.sensiolabs.org/doc/providers/session.html
        $session = $request->getSession();
        $session->start();

        // Setting session obj
        Session::setSession($session);

        UserManager::setEntityManager($app['orm.em']);

        /** @var DataFilesystem $filesystem */
        $filesystem = $app['chamilo.filesystem'];

        if ($app['debug']) {
            // Creates data/temp folders for every request if debug is on.
            $filesystem->createFolders($app['temp.paths']->folders);
        }

        // If Assetic is enabled copy folders from theme inside "web/"
        if ($app['assetic.auto_dump_assets']) {
            $filesystem->copyFolders($app['temp.paths']->copyFolders);
        }

        // Check and modify the date of user in the track.e.online table
        Online::loginCheck(api_get_user_id());

        // Setting access_url id (multiple url feature)

        if (api_get_multiple_access_url()) {
            $_configuration = $app['configuration'];
            $_configuration['access_url'] = 1;
            $access_urls = api_get_access_urls();

            $protocol = $request->getScheme().'://';
            $request_url1 = $protocol.$_SERVER['SERVER_NAME'].'/';
            $request_url2 = $protocol.$_SERVER['HTTP_HOST'].'/';

            foreach ($access_urls as & $details) {
                if ($request_url1 == $details['url'] or $request_url2 == $details['url']) {
                    $_configuration['access_url'] = $details['id'];
                }
            }
            Session::write('url_id', $_configuration['access_url']);
            Session::write('url_info', api_get_current_access_url_info($_configuration['access_url']));
        } else {
            Session::write('url_id', 1);
        }

        // Loading portal settings from DB.
        $settingsRefreshInfo = api_get_settings_params_simple(array('variable = ?' => 'settings_latest_update'));
        $settingsLatestUpdate = $settingsRefreshInfo ? $settingsRefreshInfo['selected_value'] : null;

        $settings = Session::read('_setting');

        if (empty($settings)) {
            api_set_settings_and_plugins();
        } else {
            if (isset($settings['settings_latest_update']) && $settings['settings_latest_update'] != $settingsLatestUpdate) {
                api_set_settings_and_plugins();
            }
        }

        $app['plugins'] = Session::read('_plugins');

        // Default template style.
        $templateStyle = api_get_setting('template');
        $templateStyle = isset($templateStyle) && !empty($templateStyle) ? $templateStyle : 'default';
        if (!is_dir($app['sys_root'].'main/template/'.$templateStyle)) {
            $templateStyle = 'default';
        }
        $app['template_style'] = $templateStyle;

        // Default layout.
        $app['default_layout'] = $app['template_style'].'/layout/layout_1_col.tpl';

        // Setting languages.
        $app['api_get_languages'] = api_get_languages();
        $app['language_interface'] = $language_interface = api_get_language_interface();

        // Reconfigure template now that we know the user.
        $app['template.hide_global_chat'] = !api_is_global_chat_enabled();

        /** Setting the course quota */
        // Default quota for the course documents folder
        $default_quota = api_get_setting('default_document_quotum');
        // Just in case the setting is not correctly set
        if (empty($default_quota)) {
            $default_quota = 100000000;
        }

        define('DEFAULT_DOCUMENT_QUOTA', $default_quota);

        // Specification for usernames:
        // 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 40 characters maximum length.
        // 2. Empty username is formally valid, but it is reserved for the anonymous user.
        // 3. Checking the login_is_email portal setting in order to accept 100 chars maximum

        $default_username_length = 40;
        if (api_get_setting('login_is_email') == 'true') {
            $default_username_length = 100;
        }

        define('USERNAME_MAX_LENGTH', $default_username_length);

        $user = null;

        /** Security component. */
        /** @var SecurityContext $security */
        $security = $app['security'];

        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {

            // Checking token in order to get the current user.
            $token = $security->getToken();
            if (null !== $token) {
                /** @var User $user */
                $user = $token->getUser();
                $filesystem->createMyFilesFolder($user);
            }

            // For backward compatibility.
            $userInfo = api_get_user_info($user->getUserId());
            $userInfo['is_anonymous'] = false;

            Session::write('_user', $userInfo);
            $app['current_user'] = $userInfo;

            // Setting admin permissions.
            if ($security->isGranted('ROLE_ADMIN')) {
                Session::write('is_platformAdmin', true);
            }

            // Setting teachers permissions.
            if ($security->isGranted('ROLE_TEACHER')) {
                Session::write('is_allowedCreateCourse', true);
            }

        } else {
            Session::erase('_user');
            Session::erase('is_platformAdmin');
            Session::erase('is_allowedCreateCourse');
        }

        /** Translator component. */
        $app['translator.cache.enabled'] = false;

        $language = api_get_setting('platformLanguage');
        $iso = api_get_language_isocode($language);

        /** @var Translator $translator */
        $translator = $app['translator'];
        $translator->setLocale($iso);

        // From the login page
        $language = $request->get('language');

        if (!empty($language)) {
            $iso = api_get_language_isocode($language);
            $translator->setLocale($iso);
        }

        // From the user
        if ($user && $userInfo) {
            // @todo check why this does not works
            //$language = $user->getLanguage();
            $language = $userInfo['language'];
            $iso = api_get_language_isocode($language);
            $translator->setLocale($iso);
        }

        // From the course
        $courseInfo = api_get_course_info();
        if ($courseInfo && !empty($courseInfo)) {
            $iso = api_get_language_isocode($courseInfo['language']);
            $translator->setLocale($iso);
        }

        $app['translator'] = $app->share($app->extend('translator', function ($translator, $app) {
            $locale = $translator->getLocale();

            /** @var Translator $translator  */
            if ($app['translator.cache.enabled']) {
                //$phpFileDumper = new Symfony\Component\Translation\Dumper\PhpFileDumper();
                $dumper = new MoFileDumper();
                $catalogue = new MessageCatalogue($locale);
                $catalogue->add(array('foo' => 'bar'));
                $dumper->dump($catalogue, array('path' => $app['sys_temp_path']));
            } else {
                $translationPath = $app['root_sys'].'src/ChamiloLMS/Resources/translations/';

                $translator->addLoader('pofile', new PoFileLoader());
                $file = $translationPath.$locale.'.po';
                if (file_exists($file)) {
                    $translator->addResource('pofile', $file, $locale);
                }
                $customFile = $translationPath.$locale.'.custom.po';
                if (file_exists($customFile)) {
                    $translator->addResource('pofile', $customFile, $locale);
                }

                // Validators
                $file = $app['root_sys'].'vendor/symfony/validator/Symfony/Component/Validator/Resources/translations/validators.'.$locale.'.xlf';
                $translator->addLoader('xlf', new XliffFileLoader());
                if (file_exists($file)) {
                    $translator->addResource('xlf', $file, $locale, 'validators');
                }

                /*$translator->addLoader('mofile', new MoFileLoader());
                $filePath = api_get_path(SYS_PATH).'main/locale/'.$locale.'.mo';
                if (!file_exists($filePath)) {
                    $filePath = api_get_path(SYS_PATH).'main/locale/en.mo';
                }
                $translator->addResource('mofile', $filePath, $locale);*/
                return $translator;
            }
        }));

        // Check if we are inside a Chamilo course tool
        /*$isCourseTool = (strpos($request->getPathInfo(), 'courses/') === false) ? false : true;

        if (!$isCourseTool) {
            // @todo add a before in controller in order to load the courses and course_session object
            $isCourseTool = (strpos($request->getPathInfo(), 'editor/filemanager') === false) ? false : true;
            var_dump($isCourseTool);
            var_dump(api_get_course_id());exit;
        }*/

        $studentView = $request->get('isStudentView');
        if (!empty($studentView)) {
            if ($studentView == 'true') {
                $session->set('studentview', 'studentview');
            } else {
                $session->set('studentview', 'teacherview');
            }
        }
    }
);

/** An after application middleware allows you to tweak the Response before it is sent to the client */
$app->after(
    function (Request $request, Response $response) {

    }
);

/** A "finish" application middleware allows you to execute tasks after the Response has been sent to
 * the client (like sending emails or logging) */
$app->finish(
    function (Request $request) use ($app) {

    }
);
// End Silex Middlewares

// The global variable $charset has been defined in a language file too (trad4all.inc.php), this is legacy situation.
// So, we have to reassign this variable again in order to keep its value right.
$charset = $charset_initial_value;

// The global variable $text_dir has been defined in the language file trad4all.inc.php.
// For determing text direction correspondent to the current language we use now information from the internationalization library.
$text_dir = api_get_text_direction();

/** Setting the is_admin key */
$app['is_admin'] = false;

/** Including routes */
require_once 'routes.php';

// Setting doctrine2 extensions

if (isset($app['configuration']['main_database']) && isset($app['db.event_manager'])) {

    // @todo improvement do not create every time this objects
    $sortableGroup = new Gedmo\Mapping\Annotation\SortableGroup(array());
    $sortablePosition = new Gedmo\Mapping\Annotation\SortablePosition(array());
    $tree = new Gedmo\Mapping\Annotation\Tree(array());
    $tree = new Gedmo\Mapping\Annotation\TreeParent(array());
    $tree = new Gedmo\Mapping\Annotation\TreeLeft(array());
    $tree = new Gedmo\Mapping\Annotation\TreeRight(array());
    $tree = new Gedmo\Mapping\Annotation\TreeRoot(array());
    $tree = new Gedmo\Mapping\Annotation\TreeLevel(array());
    $tree = new Gedmo\Mapping\Annotation\Versioned(array());
    $tree = new Gedmo\Mapping\Annotation\Loggable(array());
    $tree = new Gedmo\Loggable\Entity\LogEntry();

    // Setting Doctrine2 extensions
    $timestampableListener = new \Gedmo\Timestampable\TimestampableListener();
    // $app['db.event_manager']->addEventSubscriber($timestampableListener);
    $app['dbs.event_manager']['db_read']->addEventSubscriber($timestampableListener);
    $app['dbs.event_manager']['db_write']->addEventSubscriber($timestampableListener);

    $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
    // $app['db.event_manager']->addEventSubscriber($sluggableListener);
    $app['dbs.event_manager']['db_read']->addEventSubscriber($sluggableListener);
    $app['dbs.event_manager']['db_write']->addEventSubscriber($sluggableListener);

    $sortableListener = new Gedmo\Sortable\SortableListener();
    // $app['db.event_manager']->addEventSubscriber($sortableListener);
    $app['dbs.event_manager']['db_read']->addEventSubscriber($sortableListener);
    $app['dbs.event_manager']['db_write']->addEventSubscriber($sortableListener);

    $treeListener = new \Gedmo\Tree\TreeListener();
    //$treeListener->setAnnotationReader($cachedAnnotationReader);
    // $app['db.event_manager']->addEventSubscriber($treeListener);
    $app['dbs.event_manager']['db_read']->addEventSubscriber($treeListener);
    $app['dbs.event_manager']['db_write']->addEventSubscriber($treeListener);

    $loggableListener = new \Gedmo\Loggable\LoggableListener();
    if (PHP_SAPI != 'cli') {
        //$userInfo = api_get_user_info();

        if (isset($userInfo) && !empty($userInfo['username'])) {
            //$loggableListener->setUsername($userInfo['username']);
        }
    }
    $app['dbs.event_manager']['db_read']->addEventSubscriber($loggableListener);
    $app['dbs.event_manager']['db_write']->addEventSubscriber($loggableListener);
}

return $app;
