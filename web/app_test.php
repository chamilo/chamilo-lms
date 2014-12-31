<?php

if (!in_array(@$_SERVER['REMOTE_ADDR'], array(
    '127.0.0.1',
    '172.33.33.1',
    '::1',
    '10.0.0.1'
))) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Require kernel.
require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/legacy.php';



/*$request = Request::createFromGlobals();
Request::enableHttpMethodParameterOverride();*/
use Sonata\PageBundle\Request\RequestFactory;
$request = RequestFactory::createFromGlobals('host_with_path_by_locale');
$request->enableHttpMethodParameterOverride();

// Initialize kernel and run the application.
$kernel = new AppKernel('test', true);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
