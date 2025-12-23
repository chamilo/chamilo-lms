<?php

/* For license terms, see /license.txt */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script();

$plugin = ExerciseMonitoringPlugin::create();
$request = HttpRequest::createFromGlobals();
$em = Database::getManager();

$startController = new StartController($plugin, $request, $em);

$response = $startController();
$response->send();
