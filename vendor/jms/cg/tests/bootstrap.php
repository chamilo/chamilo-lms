<?php

/*
 * This file is part of the CG library.
 *
 *    (C) 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 */

spl_autoload_register(function($class)
{
    if (0 === strpos($class, 'CG\Tests\\')) {
        $path = __DIR__.'/../tests/'.strtr($class, '\\', '/').'.php';
        if (file_exists($path) && is_readable($path)) {
            require_once $path;

            return true;
        }
    } else if (0 === strpos($class, 'CG\\')) {
        $path = __DIR__.'/../src/'.($class = strtr($class, '\\', '/')).'.php';
        if (file_exists($path) && is_readable($path)) {
            require_once $path;

            return true;
        }
    } else if (0 === strpos($class, 'Zend\\')) {
        $path = __DIR__.'/../../zend-framework2/library/'.($class = strtr($class, '\\', '/')).'.php';
        if (file_exists($path) && is_readable($path)) {
            require_once $path;

            return true;
        }
    }
});

