# ConsoleServiceProvider

Provides a `Symfony\Component\Console` based console for Silex.

## Install

Add `knplabs/console-service-provider` to your `composer.json` and register the service:

```php
<?php

use Knp\Provider\ConsoleServiceProvider;

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'MyApplication',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));

?>
```

You can now copy the `console` executable in whatever place you see fit, and tweak it to your needs. You will need a way to fetch your silex application, the most common way is to return it from your bootstrap:

```php
<?php

$app = new Silex\Application();

// your beautiful silex bootstrap

return $app;

?>
```

For the rest of this document, we will assume you do have an `app` directory, so the `console` executable will be located at `app/console`.

## Usage

Use the console just like any `Symfony\Component` based console:

```
$ app/console my:command
```

## Write commands

Your commands should extend `Knp\Command\Command` to have access to the 2 useful following commands:

* `getSilexApplication`, which returns the silex application
* `getProjectDirectory`, which returns your project's root directory (as configured earlier)

I know, it's a lot to learn, but it's worth the pain.

## Register commands

There are two ways of registering commands to the console application.

### Directly access the console application from the `console` executable

Open up `app/console`, and stuff your commands directly into the console application:

```php
#!/usr/bin/env php
<?php

set_time_limit(0);

$app = require_once __DIR__.'/bootstrap.php';

use My\Command\MyCommand;

$application = $app['console'];
$application->add(new MyCommand());
$application->run();

?>
```

### Use the Event Dispatcher

This way is intended for use by provider developers and exposes an unobstrusive way to register commands in 3 simple steps:

1. Register a listener to the `ConsoleEvents::INIT` event
2. ???
3. PROFIT!

Example:

```php
<?php

use My\Command\MyCommand;
use Knp\Console\ConsoleEvents;
use Knp\Console\ConsoleEvent;

$app['dispatcher']->addListener(ConsoleEvents::INIT, function(ConsoleEvent $event) {
    $app = $event->getApplication();
    $app->add(new MyCommand());            
});

?>
```