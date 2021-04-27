<?php

use Chamilo\Kernel;

require dirname(__DIR__).'/../vendor/autoload.php';

$kernel = new Kernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
