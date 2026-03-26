<?php

/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use League\Flysystem\FilesystemOperator;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_protect_course_script();

$plugin = ExerciseMonitoringPlugin::create();
$em = Database::getManager();

/** @var FilesystemOperator $pluginsFilesystem */
$pluginsFilesystem = Container::$container->get('oneup_flysystem.plugins_filesystem');

$startController = new StartController(
    $plugin,
    Container::getRequest(),
    $em,
    $pluginsFilesystem
);

$response = $startController();
$response->send();
