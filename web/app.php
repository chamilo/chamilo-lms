<?php

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0000);

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';

// Use APC for autoloading to improve performance.
// Change 'sf2' to a unique prefix in order to prevent cache key conflicts
// with other applications also using APC.
/*
$apcLoader = new ApcClassLoader('sf2', $loader);
$loader->unregister();
$apcLoader->register(true);
*/

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';
require_once __DIR__.'/legacy.php';

use Sonata\PageBundle\Request\RequestFactory;

// if you want to use the SonataPageBundle with multisite
// using different relative paths, you must change the request
// object to use the SiteRequest
$request = RequestFactory::createFromGlobals('host_with_path_by_locale');
// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
$request->enableHttpMethodParameterOverride();

$kernel = new AppKernel('prod', false);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
