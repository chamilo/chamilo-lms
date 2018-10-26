<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

header('Content-Type: application/xml');

$plugin = ImsLtiPlugin::create();

$process = $plugin->processServiceRequest();

echo $process;
