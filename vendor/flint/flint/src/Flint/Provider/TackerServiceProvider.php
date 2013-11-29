<?php

namespace Flint\Provider;

use Silex\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Tacker\Configurator;
use Tacker\Loader\CacheLoader;
use Tacker\Loader\IniFileLoader;
use Tacker\Loader\JsonFileLoader;
use Tacker\Loader\NormalizerLoader;
use Tacker\Loader\PhpFileLoader;
use Tacker\Loader\YamlFileLoader;
use Tacker\Normalizer\ChainNormalizer;
use Tacker\Normalizer\EnvironmentNormalizer;
use Tacker\Normalizer\PimpleNormalizer;
use Tacker\ResourceCollection;

/**
 * @package Flint
 */
class TackerServiceProvider implements \Silex\ServiceProviderInterface
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
            $normalizer->add(new PimpleNormalizer($app));
            $normalizer->add(new EnvironmentNormalizer);

            return $normalizer;
        });

        $app['config.loader'] = $app->share(function (Application $app) {
            $loader = new NormalizerLoader(new DelegatingLoader($app['config.loader_resolver']), $app['config.normalizer']);
            $loader = new CacheLoader($loader, $app['config.resource_collection']);

            $loader->setCacheDir($app['config.cache_dir']);
            $loader->setDebug($app['debug']);

            return $loader;
        });

        $app['config.loader_resolver'] = $app->share(function ($app) {
            return new LoaderResolver(array(
                new JsonFileLoader($app['config.locator'], $app['config.resource_collection']),
                new IniFileLoader($app['config.locator'], $app['config.resource_collection']),
                new PhpFileLoader($app['config.locator'], $app['config.resource_collection']),
                new YamlFileLoader($app['config.locator'], $app['config.resource_collection']),
            ));
        });

        $app['configurator'] = $app->share(function (Application $app) {
            return new Configurator($app['config.loader']);
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
