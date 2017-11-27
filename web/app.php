<?php

//use Symfony\Component\ClassLoader\ApcClassLoader;
//use Symfony\Component\HttpFoundation\Request;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0000);


$loader = require __DIR__.'/../app/autoload.php';
include_once __DIR__.'/../app/bootstrap.php.cache';

// Enable APC for autoloading to improve performance.
// You should change the ApcClassLoader first argument to a unique prefix
// in order to prevent cache key conflicts with other applications
// also using APC.
/*
$apcLoader = new ApcClassLoader(sha1(__FILE__), $loader);
$loader->unregister();
$apcLoader->register(true);
*/


require_once __DIR__.'/../app/AppKernel.php';
$request = Sonata\PageBundle\Request\RequestFactory::createFromGlobals(
    'host_with_path_by_locale'
);

$kernel = new AppKernel('prod', false);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
