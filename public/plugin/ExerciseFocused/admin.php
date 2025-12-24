<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExerciseFocused\Controller\AdminController;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();
$logRepository = $em->getRepository(Log::class);

$reportingController = new AdminController(
    ExerciseFocusedPlugin::create(),
    HttpRequest::createFromGlobals(),
    $em,
    $logRepository
);

try {
    $response = $reportingController();
} catch (Exception $e) {
    $response = HttpResponse::create('', HttpResponse::HTTP_FORBIDDEN);
}

$response->send();
