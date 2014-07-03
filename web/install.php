<?php

use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Claroline\WebInstaller\Kernel;

$loader = require_once __DIR__ . '/../vendor/autoload.php';
$request = Request::createFromGlobals();

if (!file_exists($file = __DIR__ . '/../app/config/is_installed.php') || false === require_once $file) {
    AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
    $kernel = new Kernel(__DIR__ . '/../app');
    $kernel->handle($request)->send();
} else {
    header('Location: ' . $request->getBaseUrl() . '/../app.php');
}
