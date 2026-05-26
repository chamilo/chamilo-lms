<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\PluginBundle\ExerciseMonitoring\Controller\SnapshotController;

require_once __DIR__.'/../../../main/inc/global.inc.php';

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$snapshotController = new SnapshotController(
    ExerciseMonitoringPlugin::create(),
    Container::getRequest(),
);

$response = $snapshotController();
$response->send();
