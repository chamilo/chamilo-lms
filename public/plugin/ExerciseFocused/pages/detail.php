<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\ExerciseFocused\Controller\DetailController;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$logRepository = $em->getRepository(Log::class);

$detailController = new DetailController(
    ExerciseFocusedPlugin::create(),
    HttpRequest::createFromGlobals(),
    $em,
    $logRepository
);

try {
    $response = $detailController();
} catch (Exception $e) {
    $response = HttpResponse::create('', HttpResponse::HTTP_FORBIDDEN);
}

$response->send();
