<?php
/* For licensing terms, see /license.txt */

/** Composer autoload */
require_once __DIR__.'/../vendor/autoload.php';

/**
 * Classic global.inc.php file now returns a Application object
 * Make sure you read the documentation/installation_guide.html to learn how
 * to configure your VirtualHost to allow for overrides.
 */
/**
 * Inclusion of main setup script
 */
$app = require_once __DIR__.'/../src/ChamiloLMS/app.php';

/**
 * In order to execute Chamilo, you need to call the $app->run() method.
 * This method renders a page depending of the URL, for example when entering
 * to "/web/index" Chamilo will call the controller "IndexController->indexAction()". This is
 * because a router was assigned in the router.php file
 *
 *   $app->get('/index', 'index.controller:indexAction')->bind('index');
 *
 * The "index.controller:indexAction" string is transformed (due a
 * controller - service approach) into the method:
 * ChamiloLMS\Controller\IndexController->indexAction() see more
 * at: http://silex.sensiolabs.org/doc/providers/service_controller.html
 * The class is loaded automatically (no require_once needed) thanks to the
 * namespace ChamiloLMS added in Composer.
 * The location of the file is src\ChamiloLMS\Controller\IndexController.php
 * following the PSR-1 standards.
*/

/** @var \Silex\Application $app */
$app->run();
