<?php

//use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

require_once __DIR__.'/../app/bootstrap.php.cache';

require_once __DIR__.'/../app/AppKernel.php';
require_once __DIR__.'/legacy.php';

// if you want to use the SonataPageBundle with multisite
// using different relative paths, you must change the request
// object to use the SiteRequest
use Sonata\PageBundle\Request\RequestFactory;
//$request = Request::createFromGlobals('host_with_path');
$request = RequestFactory::createFromGlobals('host_with_path');


$kernel = new AppKernel('dev', true);

$kernel->loadClassCache();
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);
