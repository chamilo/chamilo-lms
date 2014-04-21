<?php

/**
 * Set up the Chamilo autoload stack. Can be called several time if needed also
 * better to avoid it.
 */

require_once dirname(__FILE__) . '/lib/autoload.class.php';
Autoload::register();

/**
use Symfony\Component\ClassLoader\UniversalClassLoader;
$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony\\Component\\HttpFoundation', __DIR__.'/vendor/symfony/http-foundation',
));
$loader->register();
 */