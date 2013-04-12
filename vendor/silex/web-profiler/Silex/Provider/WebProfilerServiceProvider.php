<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Symfony\Bundle\WebProfilerBundle\Controller\ExceptionController;
use Symfony\Bundle\WebProfilerBundle\Controller\RouterController;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;
use Symfony\Component\HttpKernel\DataCollector\ConfigDataCollector;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony\Component\HttpKernel\DataCollector\RouterDataCollector;
use Symfony\Component\HttpKernel\DataCollector\MemoryDataCollector;
use Symfony\Component\HttpKernel\DataCollector\TimeDataCollector;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Silex\ServiceControllerResolver;

/**
 * Symfony Web Profiler provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class WebProfilerServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    public function register(Application $app)
    {
        $app['dispatcher'] = $app->share($app->extend('dispatcher', function ($dispatcher, $app) {
            $dispatcher = new TraceableEventDispatcher($dispatcher, $app['stopwatch'], $app['logger']);
            $dispatcher->setProfiler($app['profiler']);

            return $dispatcher;
        }));

        $app['data_collector.templates'] = array(
            array('config',    '@WebProfiler/Collector/config.html.twig'),
            array('request',   '@WebProfiler/Collector/request.html.twig'),
            array('exception', '@WebProfiler/Collector/exception.html.twig'),
            array('events',    '@WebProfiler/Collector/events.html.twig'),
            array('logger',    '@WebProfiler/Collector/logger.html.twig'),
            array('time',      '@WebProfiler/Collector/time.html.twig'),
            array('router',    '@WebProfiler/Collector/router.html.twig'),
            array('memory',    '@WebProfiler/Collector/memory.html.twig'),
        );

        $app['data_collectors'] = array(
            'config'    => $app->share(function ($app) { return new ConfigDataCollector(); }),
            'request'   => $app->share(function ($app) { return new RequestDataCollector($app); }),
            'exception' => $app->share(function ($app) { return new ExceptionDataCollector(); }),
            'events'    => $app->share(function ($app) { return new EventDataCollector(); }),
            'logger'    => $app->share(function ($app) { return new LoggerDataCollector($app['logger']); }),
            'time'      => $app->share(function ($app) { return new TimeDataCollector(); }),
            'router'    => $app->share(function ($app) { return new RouterDataCollector(); }),
            'memory'    => $app->share(function ($app) { return new MemoryDataCollector(); }),
        );

        $app['web_profiler.controller.profiler'] = $app->share(function ($app) {
            return new ProfilerController($app['url_generator'], $app['profiler'], $app['twig'], $app['data_collector.templates'], $app['web_profiler.debug_toolbar.position']);
        });

        $app['web_profiler.controller.router'] = $app->share(function ($app) {
            return new RouterController($app['profiler'], $app['twig'], isset($app['url_matcher']) ? $app['url_matcher'] : null, $app['routes']);
        });

        $app['web_profiler.controller.exception'] = $app->share(function ($app) {
            return new ExceptionController($app['profiler'], $app['twig'], $app['debug']);
        });

        $app['web_profiler.toolbar.listener'] = $app->share(function ($app) {
            return new WebDebugToolbarListener($app['twig']);
        });

        $app['web_profiler.debug_toolbar.position'] = 'bottom';

        $app['profiler'] = $app->share(function ($app) {
            $profiler = new Profiler($app['profiler.storage'], $app['logger']);

            foreach ($app['data_collectors'] as $collector) {
                $profiler->add($collector($app));
            }

            return $profiler;
        });

        $app['profiler.storage'] = $app->share(function ($app) {
            return new FileProfilerStorage('file:'.$app['profiler.cache_dir']);
        });

        $app['profiler.request_matcher'] = null;
        $app['profiler.only_exceptions'] = false;
        $app['profiler.only_master_requests'] = false;

        $app['profiler.listener'] = $app->share(function ($app) {
            return new ProfilerListener(
                $app['profiler'],
                $app['profiler.request_matcher'],
                $app['profiler.only_exceptions'],
                $app['profiler.only_master_requests']
            );
        });

        $app['stopwatch'] = $app->share(function () {
            return new Stopwatch();
        });

        $app['code.file_link_format'] = null;

        $app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
            $twig->addExtension(new CodeExtension($app['code.file_link_format'], '', $app['charset']));

            return $twig;
        }));

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath($app['profiler.templates_path'], 'WebProfiler');

            return $loader;
        }));

        $app['profiler.templates_path'] = function () {
            $r = new \ReflectionClass('Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener');

            return dirname(dirname($r->getFileName())).'/Resources/views';
        };
    }

    public function connect(Application $app)
    {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use the WebProfiler.');
        }

        $controllers = $app['controllers_factory'];

        $controllers->get('/router/{token}', 'web_profiler.controller.router:panelAction')->bind('_profiler_router');
        $controllers->get('/exception/{token}.css', 'web_profiler.controller.exception:cssAction')->bind('_profiler_exception_css');
        $controllers->get('/exception/{token}', 'web_profiler.controller.exception:showAction')->bind('_profiler_exception');
        $controllers->get('/search', 'web_profiler.controller.profiler:searchAction')->bind('_profiler_search');
        $controllers->get('/search_bar', 'web_profiler.controller.profiler:searchBarAction')->bind('_profiler_search_bar');
        $controllers->get('/purge', 'web_profiler.controller.profiler:purgeAction')->bind('_profiler_purge');
        $controllers->get('/info/{about}', 'web_profiler.controller.profiler:infoAction')->bind('_profiler_info');
        $controllers->get('/import', 'web_profiler.controller.profiler:importAction')->bind('_profiler_import');
        $controllers->get('/export/{token}.txt', 'web_profiler.controller.profiler:exportAction')->bind('_profiler_export');
        $controllers->get('/phpinfo', 'web_profiler.controller.profiler:phpinfoAction')->bind('_profiler_phpinfo');
        $controllers->get('/{token}/search/results', 'web_profiler.controller.profiler:searchResultsAction')->bind('_profiler_search_results');
        $controllers->get('/{token}', 'web_profiler.controller.profiler:panelAction')->bind('_profiler');
        $controllers->get('/wdt/{token}', 'web_profiler.controller.profiler:toolbarAction')->bind('_wdt');
        $controllers->get('/', 'web_profiler.controller.profiler:homeAction')->bind('_profiler_home');

        return $controllers;
    }

    public function boot(Application $app)
    {
        $dispatcher = $app['dispatcher'];

        $dispatcher->addSubscriber($app['profiler.listener']);
        $dispatcher->addSubscriber($app['web_profiler.toolbar.listener']);
        $dispatcher->addSubscriber($app['profiler']->get('request'));
    }
}
