InstallerBundle
==================

Web installer. Inspired by [Sylius](https://github.com/Sylius/SyliusInstallerBundle).

To run the installer on existing setup, you need to update parameters.yml file:
``` yaml
# ...
session_handler: ~
installed: ~
```

## Usage ##
If you are using distribution package, you will be redirected to installer page automatically.

Otherwise, following installation instructions offered:
``` bash
$ git clone https://github.com/chamilo/chamilo-lms.git
$ cd chamilo-lms
$ wget http://getcomposer.org/composer.phar
$ php composer.phar install
$ php app/console chamilo:install
```

## Events ##
To add additional actions to the installation process you may use event listeners.
Currently only "onFinish" installer event dispatched.

Example:

``` yaml
services:
    installer.listener.finish.event:
        class:  Acme\Bundle\MyBundle\EventListener\MyListener
        tags:
            - { name: kernel.event_listener, event: installer.finish, method: onFinish }
```

``` php
<?php

namespace Acme\Bundle\MyBundle\EventListener;

class MyListener
{
    public function onFinish()
    {
        // do something
    }
}

```

## Sample data ##
To provide demo fixtures for your bundle just place them in "YourBundle\Data\Demo" directory.

## Additional install files in bundles and packages ##

To add additional install scripts during install process you can use install.php files in your bundles and packages.
This install files will be run before last clear cache during installation.

This file must be started with `@ChamiloScript` annotation with script label which will be shown during web install process.

Example:
``` php
<?php
/**
 * @ChamiloScript("Your script label")
 */

 // here you can add additional install logic

```

The following variables are available in installer script:

 - `$container` - Symfony2 DI container
 - `$commandExecutor` - An instance of [CommandExecutor](./CommandExecutor.php) class. You can use it to execute Symfony console commands

All outputs from installer script will be logged in chamilo_install.log file or will be shown in console in you use console installer.

## Launching plain PHP script in Chamilo context ##
In some cases you may need to launch PHP scripts in context of Platform. It means that you need an access to Symfony DI container. Examples of such cases may be some installation or maintenance sctipts. To achieve this you can use `chamilo:platform:run-script` command.
Each script file must be started with `@ChamiloScript` annotation. For example:
``` php
<?php
/**
 * @ChamiloScript("Your script label")
 */

 // here you can write some PHP code

```

The following variables are available in a script:

 - `$container` - Symfony2 DI container
 - `$commandExecutor` - An instance of [CommandExecutor](./CommandExecutor.php) class. You can use it to execute Symfony console commands

