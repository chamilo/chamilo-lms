<?php

namespace SilexAssetic\Tests;

use Silex\Application;

use SilexAssetic\AsseticServiceProvider;

use Symfony\Component\HttpFoundation\Request;

class AsseticExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Assetic\\AssetManager')) {
            $this->markTestSkipped('Assetic was not installed.');
        }
    }

    public function testEverythingLoaded()
    {
        $app = new Application();

        $app->register(new AsseticServiceProvider());
        $app['assetic.path_to_web'] = sys_get_temp_dir();

        $app->get('/', function () use ($app) {
            return 'AsseticExtensionTest';
        });

        $request  = Request::create('/');
        $response = $app->handle($request);

        $this->assertInstanceOf('Assetic\Factory\AssetFactory', $app['assetic']);
        $this->assertInstanceOf('Assetic\AssetManager', $app['assetic.asset_manager']);
        $this->assertInstanceOf('Assetic\FilterManager', $app['assetic.filter_manager']);
        $this->assertInstanceOf('Assetic\AssetWriter', $app['assetic.asset_writer']);
        $this->assertInstanceOf('Assetic\Factory\LazyAssetManager', $app['assetic.lazy_asset_manager']);
    }

    public function testFilterFormRegistration()
    {
        $app = new Application();
        $app->register(new AsseticServiceProvider());
        $app['assetic.path_to_web'] = sys_get_temp_dir();

        $app['assetic.filter_manager'] = $app->share(
            $app->extend('assetic.filter_manager', function($fm, $app) {
                $fm->set('test_filter', new \Assetic\Filter\CssMinFilter());

                return $fm;
            })
        );

        $app->get('/', function () use ($app) {
            return 'AsseticExtensionTest';
        });

        $request  = Request::create('/');
        $response = $app->handle($request);

        $this->assertTrue($app['assetic.filter_manager']->has('test_filter'));
        $this->assertInstanceOf('Assetic\Filter\CssMinFilter', $app['assetic.filter_manager']->get('test_filter'));
    }

    public function testAssetFormRegistration()
    {
        $app = new Application();
        $app->register(new AsseticServiceProvider());
        $app['assetic.path_to_web'] = sys_get_temp_dir();

        $app['assetic.asset_manager'] = $app->share(
            $app->extend('assetic.asset_manager', function($am, $app) {
                $asset = new \Assetic\Asset\FileAsset(__FILE__);
                $asset->setTargetPath(md5(__FILE__));
                $am->set('test_asset', $asset);

                return $am;
            })
        );

        $app->get('/', function () {
            return 'AsseticExtensionTest';
        });

        $request  = Request::create('/');
        $response = $app->handle($request);

        $this->assertTrue($app['assetic.asset_manager']->has('test_asset'));
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $app['assetic.asset_manager']->get('test_asset'));
        $this->assertTrue(file_exists(sys_get_temp_dir() . '/' . md5(__FILE__)));
    }

    public function testTwigAddExtension()
    {
        if (!class_exists('Twig_Environment')) {
            $this->markTestSkipped('Twig was not installed.');
        }

        $app = new Application();

        $app['twig'] = $app->share(function () {
            return new \Twig_Environment(new \Twig_Loader_String());
        });

        $app->register(new AsseticServiceProvider());
        $app['assetic.path_to_web'] = sys_get_temp_dir();

        $this->assertInstanceOf('Assetic\\Extension\\Twig\\AsseticExtension', $app['twig']->getExtension('assetic'));
    }

    public function tearDown()
    {
        if (file_exists(sys_get_temp_dir() . '/' . md5(__FILE__))) {
            unlink(sys_get_temp_dir() . '/' . md5(__FILE__));
        }
    }
}
