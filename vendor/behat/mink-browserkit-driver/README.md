Mink BrowserKit Driver
======================

- [![Build Status](https://secure.travis-ci.org/Behat/MinkBrowserKitDriver.png?branch=master)](http://travis-ci.org/Behat/MinkBrowserKitDriver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\BrowserKitDriver;

use Symfony\Component\HttpKernel\Client;

$app  = require_once(__DIR__.'/app.php'); // Silex app

$mink = new Mink(array(
    'silex' => new Session(new BrowserKitDriver(new Client($app))),
));

$mink->getSession('silex')->getPage()->findLink('Chat')->click();
```

Installation
------------

``` json
{
    "require": {
        "behat/mink":                   "1.4.*",
        "behat/mink-browserkit-driver": "1.0.*"
    }
}
```

``` bash
$> curl http://getcomposer.org/installer | php
$> php composer.phar install
```

Maintainers
-----------

* Konstantin Kudryashov [everzet](http://github.com/everzet)
* Other [awesome developers](https://github.com/Behat/MinkBrowserKitDriver/graphs/contributors)
