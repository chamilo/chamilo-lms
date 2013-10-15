<?php

namespace SilexOpauth; // Non psr-0 namespace usage. :(


use Opauth;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class OpauthExtension implements ServiceProviderInterface {

    /** @var Application */
    protected $app;
    protected $serviceConfig;
    
    const EVENT_ERROR = 'opauth.error';
    const EVENT_SUCCESS = 'opauth.success';

    public function register(Application $app) {
        $this->app = $app;
        $this->serviceConfig = $app['opauth'];
        $this->serviceConfig['config'] = array_merge(
                array(
                    'path' => $app['opauth']['login'] . '/',
                    'callback_url' => $app['opauth']['callback'], // Handy shortcut.
                    'callback_transport' => 'post' // Won't work with silex session
                ), $app['opauth']['config']
              );

        $that = $this;
        $app->match($this->serviceConfig['callback'], function () use ($that) {
          return $that->loginCallback();
        });

        $config = $this->serviceConfig['config'];
        $init = function () use ($config) {
            new Opauth($config);
            return '';
        };

        $app->match($this->serviceConfig['login'] . '/{strategy}', $init);
        $app->match($this->serviceConfig['login'] . '/{strategy}/{return}', $init);

    }

    public function loginCallback() {
        $Opauth = new Opauth($this->serviceConfig['config'], false);

        $response = unserialize(base64_decode($_POST['opauth']));

        $failureReason = null;
        /**
         * Check if it's an error callback
         */
        if (array_key_exists('error', $response)) {
            return $this->onAuthenticationError($response['error'], $response);
        }

        /**
         * Auth response validation
         *
         * To validate that the auth response received is unaltered, especially auth response that
         * is sent through GET or POST.
         */ else {
            if (empty($response['auth']) || empty($response['timestamp']) || empty($response['signature']) || empty($response['auth']['provider']) || empty($response['auth']['uid'])) {
                return $this->onAuthenticationError('Missing key auth response components', $response);
            } elseif (!$Opauth->validate(sha1(print_r($response['auth'], true)), $response['timestamp'], $response['signature'], $failureReason)) {
                return $this->onAuthenticationError($failureReason, $response);
            } else {
                return $this->onAuthenticationSuccess($response);
            }
        }
        
        return '';
    }

    protected function onAuthenticationError($message, $response) {
        $e = new GenericEvent($response, array('message' => $message));
        $e->setArgument('result', '');
        return $this->app['dispatcher']->dispatch(self::EVENT_ERROR, $e)->getArgument('result');
    }
    
    protected function onAuthenticationSuccess($response) {
        $e = new GenericEvent($response);
        $e->setArgument('result', '');
        return $this->app['dispatcher']->dispatch(self::EVENT_SUCCESS, $e)->getArgument('result');
    }
    
    public function boot(Application $app) {
        
    }


}