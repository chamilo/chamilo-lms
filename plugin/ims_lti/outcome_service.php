<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = ImsLtiPlugin::create();

$process = $plugin->processServiceRequest();

error_log($process);

echo $process;
