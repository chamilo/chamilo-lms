<?php

namespace Knp\Provider;

use Silex\ServiceProviderInterface;
use Silex\Application;

use Knp\Console\Application as ConsoleApplication;
use Knp\Console\ConsoleEvents;
use Knp\Console\ConsoleEvent;

class ConsoleServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['console'] = $app->share(function() use ($app) {
            $application = new ConsoleApplication(
                $app,
                $app['console.project_directory'],
                $app['console.name'],
                $app['console.version']
            );

            $app['dispatcher']->dispatch(ConsoleEvents::INIT, new ConsoleEvent($application));

            return $application;
        });
    }

    public function boot(Application $app)
    {
    }
}
