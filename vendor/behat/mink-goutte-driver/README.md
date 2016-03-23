Mink Goutte Driver
==================

- [![Build Status](https://secure.travis-ci.org/Behat/MinkGoutteDriver.png?branch=master)](http://travis-ci.org/Behat/MinkGoutteDriver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\GoutteDriver,
    Behat\Mink\Driver\Goutte\Client as GoutteClient;

$startUrl = 'http://example.com';

$mink = new Mink(array(
    'goutte' => new Session(new GoutteDriver(new GoutteClient($startUrl))),
));

$mink->getSession('goutte')->getPage()->findLink('Chat')->click();
```

Installation
------------

``` json
{
    "require": {
        "behat/mink":               "1.4.*",
        "behat/mink-goutte-driver": "1.0.*"
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
* Other [awesome developers](https://github.com/Behat/MinkGoutteDriver/graphs/contributors)
