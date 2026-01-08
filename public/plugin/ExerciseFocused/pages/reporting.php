<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\ExerciseFocused\Controller\ReportingController;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$em = Database::getManager();
$logRepository = $em->getRepository(Log::class);

$startController = new ReportingController(
    ExerciseFocusedPlugin::create(),
    Container::getRequest(),
    $em,
    $logRepository
);

$response = $startController();
$response->send();
