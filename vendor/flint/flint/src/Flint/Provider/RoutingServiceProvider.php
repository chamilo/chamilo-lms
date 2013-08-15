<?php

namespace Flint\Provider;

use Silex\Application;
use Flint\Routing\Loader\NullLoader;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Loader\DelegatingLoader;

/**
 * @package Flint
 */
class RoutingServiceProvider implements \Silex\ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['routing.resource'] = null;
        $app['routing.options'] = array();

        $app['routing.loader.xml'] = $app->share(function (Application $app) {
            return new XmlFileLoader($app['config.locator']);
        });

        $app['routing.loader.php'] = $app->share(function (Application $app) {
            return new PhpFileLoader($app['config.locator']);
        });

        $app['routing.loader.yml'] = $app->share(function (Application $app) {
            return new YamlFileLoader($app['config.locator']);
        });

        $app['routing.loader.null'] = $app->share(function (Application $app) {
            return new NullLoader;
        });

        $app['routing.loader.resolver'] = $app->share(function (Application $app) {
            $loaders = array(
                $app['routing.loader.xml'],
                $app['routing.loader.php'],
                $app['routing.loader.null'],
            );

            if (class_exists('Symfony\\Component\\Yaml\\Yaml')) {
                $loaders[] = $app['routing.loader.yml'];
            }

            return new LoaderResolver($loaders);
        });

        $app['routing.loader'] = $app->share(function (Application $app) {
            return new DelegatingLoader($app['routing.loader.resolver']);
        });

        $app['router'] = $app->share(function (Application $app) {
            $defaults = array(
                'debug'              => $app['debug'],
                'matcher_base_class' => 'Silex\\RedirectableUrlMatcher',
                'matcher_class'      => 'Silex\\RedirectableUrlMatcher',
            );

            $options = array_replace($defaults, $app['routing.options']);

            return new Router($app['routing.loader'], $app['routing.resource'], $options, $app['request_context'], $app['logger']);
        });

        $app['routes'] = function (Application $app) {
            return $app['router']->getRouteCollection();
        };

        $app['url_matcher'] = $app->raw('router');
        $app['url_generator'] = $app->raw('router');
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
