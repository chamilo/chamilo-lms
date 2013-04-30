<?php

$app = new Silex\Application();

$app->register(new Silex\Extension\TwigExtension());

$app['twig.path'] = __DIR__ . '/twig';

$app->register(new SilexAssetic\AsseticServiceProvider());

$app['assetic.path_to_web'] = __DIR__ . '/assetic/output';
$app['assetic.options'] = array(
    'formulae_cache_dir' => __DIR__ . '/assetic/cache',
    'debug'              => false
);
$app['assetic.filter_manager'] = $app['assetic.filter_manager'] = $app->share(
    $app->extend('assetic.filter_manager', function($fm, $app) {
        $fm->set('yui_css', new Assetic\Filter\Yui\CssCompressorFilter(
            '/usr/share/yui-compressor/yui-compressor.jar'
        ));
        $fm->set('yui_js', new Assetic\Filter\Yui\JsCompressorFilter(
            '/usr/share/yui-compressor/yui-compressor.jar'
        ));

        return $fm;
    })
);
$app['assetic.asset_manager'] = $app->share(
    $app->extend('assetic.asset_manager', function($am, $app) {
        $am->set('styles', new Assetic\Asset\AssetCache(
            new Assetic\Asset\GlobAsset(
                __DIR__ . '/assetic/resources/css/*.css',
                array($fm->get('yui_css'))
            ),
            new Assetic\Cache\FilesystemCache(__DIR__ . '/assetic/cache')
        ));
        $am->get('styles')->setTargetPath('css/styles');

        return $am;
    })
);

$app->get('/', function () use ($app) {
    return 'Hello!';
});

$app->run();
