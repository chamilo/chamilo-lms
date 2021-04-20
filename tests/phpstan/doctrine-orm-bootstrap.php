<?php

use Chamilo\Kernel;

require dirname(__DIR__).'/../vendor/autoload.php';

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
