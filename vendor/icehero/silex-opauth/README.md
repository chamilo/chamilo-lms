# Usage

## Simple, event driven silex extension

```php
    // Configure opauth
    $app['opauth'] = array(
      'login' => '/auth',
      'callback' => '/auth/callback',
      'config' => array(
        'security_salt' => '...your salt...',
        'Strategy' => array(
            'Facebook' => array(
               'app_id' => '...',
               'app_secret' => '...'
             ),
        )
      )
    );

    // Enable extension
    $app->register(new OpauthExtension());

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
```

## Advanced, symfony security listener+provider

Note, that you can use it in symfony2 projects too!

To login using opauth use /login/PROVIDER, or use `opauth_default_login` route with `provider` parameter.

```php

    $app->register(new OpauthSilexProvider());
    $app->register(new SecurityServiceProvider(), array(
        'security.firewalls' => array(
            'default' => array(
                'pattern' => '^/.*',
                'opauth' => array(
                    // 'check_path' => '/login/opauth', //default
                    'opauth' => [
                        // 'path' => '/login', //default
                        'security_salt' => '...your salt...',
                        'Strategy' => [
                            // your opauth strategies go here
                        ]
                    ]
                ),
                'anonymous' => true,
            ),
        )
    );

```

By default, users will be looked up by username "provider:uid".

You should extend your user provider to handle OPauth results correctly by implementing `OpauthUserProviderInterface`.

