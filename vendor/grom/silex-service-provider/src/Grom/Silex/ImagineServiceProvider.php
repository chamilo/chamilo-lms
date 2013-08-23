<?php

namespace Grom\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Silex service provider to integrate Imagine library.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
class ImagineServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
    }

    public function register(Application $app)
    {
        if(!isset($app['imagine.factory'])) {
            $app['imagine.factory'] = 'Gd';
        }

        $app['imagine'] = $app->share(function ($app) {
            $class = sprintf('\Imagine\%s\Imagine', $app['imagine.factory']);
            return new $class();
        });
    }
}
