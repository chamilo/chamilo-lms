<?php
/* For licensing terms, see /license.txt */

/**
 * This is a bootstrap file that loads all Chamilo dependencies including:
 *
 * - Chamilo settings config/configuration.yml or config/configuration.php
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

use Chamilo\CoreBundle\Framework\Application;
use \ChamiloSession as Session;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\HttpFoundation\Response;

/*
* Setup Patchwork UTF-8 Handling
*
* The Patchwork library provides solid handling of UTF-8 strings as well
* as provides replacements for all mb_* and iconv type functions that
* are not available by default in PHP. We'll setup this stuff here.
*/

//Patchwork\Utf8\Bootup::initMbstring();
//Patchwork\Utf8\Bootup::initAll();

$app = new Application();

// Installing Chamilo paths.
$app->bindInstallPaths(require __DIR__ . '/paths.php');

// Reading configuration files.
$app->readConfigurationFiles();

$alreadyInstalled = $app->isInstalled();

$basePath = $app['path.base'];

/** Including legacy libs */
require_once $basePath . 'main/inc/lib/api.lib.php';
// @todo remove $_configuration['url_append'] calls
$libPath = $basePath.'/main/inc/lib/';

// Database constants
require_once $libPath . 'database.constants.inc.php';

// @todo Rewrite the events.lib.inc.php in a class
require_once $libPath . 'events.lib.inc.php';

// Load allowed tag definitions for kses and/or HTMLPurifier.
require_once $libPath . 'formvalidator/Rule/allowed_tags.inc.php';

$app['app.theme'] = 'chamilo';

// Developer options relies in the configuration.php file.
$app['debug'] = isset($app->getConfiguration()->debug) ? $app->getConfiguration()->debug : false;
$app['show_profiler'] = isset($app->getConfiguration()->show_profiler) ? $app->getConfiguration()->show_profiler : false;

// Enables assetic in order to load 1 compressed stylesheet or split files
//$app['assetic.enabled'] = $app['debug'];
// Hardcoded to false by default. Implementation is not finished yet.
$app['assetic.enabled'] = false;

// Dumps assets
$app['assetic.auto_dump_assets'] = false;

// Loading $app settings depending of the debug option
if ($app['debug']) {
    require_once $app['path.app'].'Resources/config/dev.php';
} else {
    require_once $app['path.app'].'Resources/config/prod.php';
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

// Inclusion of internationalization libraries
require_once $libPath . 'internationalization.lib.php';

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
$app['language'] = 'english';

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

        if (!is_dir($app['path.base'].'main/template/'.$templateStyle)) {
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

$app['cidReset'] = null;

require_once $app['path.app'].'filters.php';

/** Setting the is_admin key */
$app['is_admin'] = false;

/** Including routes */
require_once $app['path.app'].'routes.php';

// Setting doctrine2 extensions
$app->setupDoctrineExtensions();

return $app;
