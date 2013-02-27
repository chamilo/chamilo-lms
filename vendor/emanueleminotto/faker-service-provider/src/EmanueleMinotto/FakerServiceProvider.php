<?php

namespace EmanueleMinotto;

use Silex\Application,
    Silex\ServiceProviderInterface,
    Faker\Factory;

/**
 * A Faker service provider for Silex
 * 
 * @author Emanuele Minotto <minottoemanuele@gmail.com>
 * @link http://silex.sensiolabs.org/doc/providers.html#creating-a-provider Creating a provider
 */
class FakerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $Application)
    {
        // ...
    }

    public function boot(Application $Application)
    {
        $Application['faker'] = Factory::create($Application['locale']);
    }
}
