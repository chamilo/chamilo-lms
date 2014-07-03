# Installation

To install the Ivory JSON builder library, you will need [Composer](http://getcomposer.org). It's a PHP 5.3+ dependency
manager which allows you to declare the dependent libraries your project needs and it will install & autoload them for
you.

## Set up Composer

Composer comes with a simple phar file. To easily access it from anywhere on your system, you can execute:

```
$ curl -s https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

## Define dependencies

Create a ``composer.json`` file at the root directory of your project and simply require the
``egeloen/json-builder`` package:

```
{
    "require": {
        "egeloen/json-builder": "~1.0"
    }
}
```

## Install dependencies

Now, you have define your dependencies, you can install them:

```
$ composer install
```

Composer will automatically download your dependencies & create an autoload file in the ``vendor`` directory.

## Autoload

So easy, you just have to require the generated autoload file and you are already ready to play:

``` php
<?php

require __DIR__.'/vendor/autoload.php';

use Ivory\JsonBuilder;

// ...
```

The Ivory JSON builder library follows the [PSR-0 Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md).
If you prefer install it manually, it can be autoload by any convenient autoloader.
