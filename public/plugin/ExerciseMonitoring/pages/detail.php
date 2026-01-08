<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\ExerciseMonitoring\Controller\DetailController;
use Chamilo\PluginBundle\ExerciseMonitoring\Entity\Log;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$logRepository = $em->getRepository(Log::class);

$detailController = new DetailController(
    ExerciseMonitoringPlugin::create(),
    Container::getRequest(),
    $em,
    $logRepository
);

$response = $detailController();
$response->send();
