<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\ExerciseFocused\Controller\AdminController;
use Chamilo\PluginBundle\ExerciseFocused\Entity\Log;
use Chamilo\PluginBundle\ExerciseFocused\Repository\LogRepository;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$em = Database::getManager();
/** @var LogRepository $logRepository */
$logRepository = $em->getRepository(Log::class);

$reportingController = new AdminController(
    ExerciseFocusedPlugin::create(),
    Container::getRequest(),
    $em,
    $logRepository
);

$response = $reportingController();
$response->send();
