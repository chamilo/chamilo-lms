<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExerciseFocused\Controller\ReportingController;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$logRepository = $em->getRepository(Log::class);

$startController = new ReportingController(
    ExerciseFocusedPlugin::create(),
    HttpRequest::createFromGlobals(),
    $em,
    $logRepository
);

//try {
    $response = $startController();
//} catch (Exception $e) {
    //$response = HttpResponse::create('', HttpResponse::HTTP_FORBIDDEN);
//}

$response->send();
