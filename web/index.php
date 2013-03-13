<?php
/* For licensing terms, see /license.txt */

//Classic global.inc.php file now returns a Application object
$app = require_once '../main/inc/global.inc.php';

/*
    In order to execute Chamilo, $app->run() is needed.
    $app->run(); shows a page depending of the URL for example when entering in "/web/index"
    Chamilo will render the Controller IndexController->indexAction() this is because a router was assign at the end of
    global.inc.php:

        $app->get('/index', 'index.controller:indexAction')->bind('index');

    The "index.controller:indexAction" string is transformed (due a controller - service approach) into
    ChamiloLMS\Controller\IndexController->indexAction() see more at: http://silex.sensiolabs.org/doc/providers/service_controller.html
    The class is loaded automatically (no require_once needed) thanks to the namespace ChamiloLMS added in Composer.
    The location of the file is src\ChamiloLMS\Controller\IndexController.php following the PSR-1 standards.
*/
$app->run();
//$app['http_cache']->run();