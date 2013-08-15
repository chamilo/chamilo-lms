<?php

namespace Flint\Provider;

use Flint\Config\Configurator;
use Flint\Config\ResourceCollection;
use Flint\Config\Loader\JsonFileLoader;
use Flint\Config\Normalizer\ChainNormalizer;
use Flint\Config\Normalizer\EnvironmentNormalizer;
use Flint\Config\Normalizer\PimpleAwareNormalizer;
use Silex\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;

/**
 * @package Flint
 */
class ConfigServiceProvider implements \Silex\ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['config.paths'] = function (Application $app) {
            return array($app['root_dir'] . '/config', $app['root_dir']);
        };

        $app['config.locator'] = $app->share(function (Application $app) {
            return new FileLocator($app['config.paths']);
        });

        $app['config.resource_collection'] = $app->share(function (Application $app) {
            return new ResourceCollection;
        });

        $app['config.normalizer'] = $app->share(function (Application $app) {
            $normalizer = new ChainNormalizer;
            $normalizer->add(new PimpleAwareNormalizer($app));
            $normalizer->add(new EnvironmentNormalizer);

            return $normalizer;
        });

        $app['config.loader'] = $app->share(function (Application $app) {
            return new DelegatingLoader($app['config.loader_resolver']);
        });

        $app['config.loader_resolver'] = $app->share(function ($app) {
            $loader = new JsonFileLoader($app['config.normalizer'], $app['config.locator'], $app['config.resource_collection']);

            return new LoaderResolver(array($loader));
        });

        $app['configurator'] = $app->share(function (Application $app) {
            $configurator = new Configurator($app['config.loader'], $app['config.resource_collection']);
            $configurator->setCacheDir($app['config.cache_dir']);
            $configurator->setDebug($app['debug']);

            return $configurator;
        });

        if (!isset($app['config.cache_dir'])) {
            $app['config.cache_dir'] = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
