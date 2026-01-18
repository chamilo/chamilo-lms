<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script();

$plugin = ExerciseMonitoringPlugin::create();
$em = Database::getManager();

$exerciseSubmitController = new ExerciseSubmitController(
    $plugin,
    Container::getRequest(),
    $em
);

$response = $exerciseSubmitController();
$response->send();
