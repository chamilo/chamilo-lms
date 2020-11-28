<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\Lrs\LrsRequest;

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$lrsRequest = new LrsRequest();
$lrsRequest->send();
