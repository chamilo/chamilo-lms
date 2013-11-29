<?php

namespace SilexOpauth\Security;


use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * @author Rafal Lindemann
 *  */
class OpauthSilexProvider implements ServiceProviderInterface
{

    protected $onBoot = array();

    public function register(Application $app)
    {

        $this->registerListener($app);
    }
    
    protected function registerListener(Application $app)
    {
        
        $app['security.authentication_listener.factory.opauth'] = $app->protect(function ($name, $options) use ($app) {
            
            $options = array_merge_recursive($options, array(
                'check_path' => '/login/opauth',
                'opauth' => array(
                    'path' => '/login/',
                )
            ));
            
            if (!isset($app['security.authentication.success_handler.'.$name])) {
                $app['security.authentication.success_handler.'.$name] = $app['security.authentication.success_handler._proto']($name, $options);
            }

            if (!isset($app['security.authentication.failure_handler.'.$name])) {
                $app['security.authentication.failure_handler.'.$name] = $app['security.authentication.failure_handler._proto']($name, $options);
            }
            
            // define the authentication provider object
            if (!isset($app['security.authentication_provider.'.$name.'.opauth'])) {
                $app['security.authentication_provider.'.$name.'.opauth'] = $app->share(function () use ($app) {
                    return new OpauthProvider($app['security.user_provider.default']);
                });
            }

            // define the authentication listener object
            if (!isset($app['security.authentication_listener.'.$name.'.opauth'])) {
                $app['security.authentication_listener.'.$name.'.opauth'] = $app->share(function () use ($app, $name, $options) {
                    return new OpauthListener(
                        $app['security'],
                        $app['security.authentication_manager'],
                        isset($app['security.session_strategy.'.$name]) ? $app['security.session_strategy.'.$name] : $app['security.session_strategy'],
                        $app['security.http_utils'],
                        $name,
                        $app['security.authentication.success_handler.'.$name],
                        $app['security.authentication.failure_handler.'.$name],
                        $options,
                        $app['logger'],
                        $app['dispatcher']
                    );
                });
            }

            // routes
//            $this->onBoot[] = function() use ($app, $options, $name) {
                $bindName = "opauth_{$name}_";
                $app->match($options['check_path'], function() {})->bind($bindName . 'check');
                $app->match($options['opauth']['path'] . '{strategy}/{return}', function() {})
                    ->value('return', '')
                    ->bind($bindName . 'login');
//            };
            
            return array(
                // the authentication provider id
                'security.authentication_provider.'.$name.'.opauth',
                // the authentication listener id
                'security.authentication_listener.'.$name.'.opauth',
                // the entry point id
                null,
                // the position of the listener in the stack
                'pre_auth'
            );
        });        
    }

    
    public function boot(Application $app)
    {
        foreach ($this->onBoot as $c) {
            call_user_func($c);
        }
    }


}