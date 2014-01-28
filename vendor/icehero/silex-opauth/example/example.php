<?php

$loader = require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application,
    SilexOpauth\OpauthExtension;

$app = new Application();

$app['debug'] = true;

$app['opauth'] = array(
  'login' => '/auth/login', // Generates a path /auth/login/{strategy}
  'callback' => '/auth/callback',
  'config' => array(
    'security_salt' => '_SECURE_RANDOM_SALT_',
    'Strategy' => array(
        'Facebook' => array( // Is available at /auth/login/facebook
           'app_id' => 'APP_ID',
           'app_secret' => 'APP_SECRETE'
         ),
    )
  )
);

$app->register(new OpauthExtension($app));


// Listen for events
$app->on(OpauthExtension::EVENT_ERROR, function($e) {
    $this->log->error('Auth error: ' . $e['message'], ['response' => $e->getSubject()]);
    $e->setArgument('result', $this->redirect('/'));
});

$app->on(OpauthExtension::EVENT_SUCCESS, function($e) {
    $response = $e->getSubject();

    /*
       find/create a user, oauth response is in $response and it's already validated!
       store the user in the session
    */

    $e->setArgument('result', $this->redirect('/'));
});
$app->run();
