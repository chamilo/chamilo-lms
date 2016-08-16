Mink Selenium2 (webdriver) Driver
=================================
[![Latest Stable Version](https://poser.pugx.org/behat/mink-selenium2-driver/v/stable.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Latest Unstable Version](https://poser.pugx.org/behat/mink-selenium2-driver/v/unstable.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Total Downloads](https://poser.pugx.org/behat/mink-selenium2-driver/downloads.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)
[![Build Status](https://travis-ci.org/minkphp/MinkSelenium2Driver.svg?branch=master)](https://travis-ci.org/minkphp/MinkSelenium2Driver)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/minkphp/MinkSelenium2Driver/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/minkphp/MinkSelenium2Driver/)
[![Code Coverage](https://scrutinizer-ci.com/g/minkphp/MinkSelenium2Driver/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/minkphp/MinkSelenium2Driver/)
[![License](https://poser.pugx.org/behat/mink-selenium2-driver/license.svg)](https://packagist.org/packages/behat/mink-selenium2-driver)

Usage Example
-------------

``` php
<?php

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\Selenium2Driver;

use Selenium\Client as SeleniumClient;

$url = 'http://example.com';

$mink = new Mink(array(
    'selenium2' => new Session(new Selenium2Driver($browser, null, $url)),
));

$mink->getSession('selenium2')->getPage()->findLink('Chat')->click();
```

Please refer to [MinkExtension-example](https://github.com/Behat/MinkExtension-example) for an executable example.

Installation
------------

``` json
{
    "require": {
        "behat/mink":                   "~1.5",
        "behat/mink-selenium2-driver":  "~1.1"
    }
}
```

``` bash
$> curl -sS http://getcomposer.org/installer | php
$> php composer.phar install
```

Copyright
---------

Copyright (c) 2012 Pete Otaqui <pete@otaqui.com>.

Maintainers
-----------

* Christophe Coevoet [stof](https://github.com/stof)
* Pete Otaqui [pete-otaqui](http://github.com/pete-otaqui)
