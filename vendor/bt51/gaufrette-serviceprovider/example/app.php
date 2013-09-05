<?php

use Silex\Application;
use Bt51\Silex\Provider\GaufretteServiceProvider\GaufretteServiceProvider;

$app = new Application();

$app->register(new GaufretteServiceProvider(), array('gaufrette.adapter.class' => 'Local',
                                                     'gaufrette.options' => array(__DIR__ . '/media')));

$app->get('/', function () use ($app) {
    try {
        $content = $app['gaufrette.filesystem']->read('/test.txt');
    } catch (\InvalidArgumentException $e) {
        return $app->abort(404, 'No file found');
    }
    
    return $content;
});
