<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\ExerciseFocused\Controller\LogController;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script(true);

$em = Database::getManager();
$logRepository = $em->getRepository(Log::class);

$logController = new LogController(
    ExerciseFocusedPlugin::create(),
    Container::getRequest(),
    $em,
    $logRepository
);

$response = $logController();
$response->send();
