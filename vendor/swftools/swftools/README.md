#PHP Swftools

[![Build Status](https://secure.travis-ci.org/alchemy-fr/PHPSwftools.png?branch=master)](http://travis-ci.org/alchemy-fr/PHPSwftools)

PHP Swftools is a tiny lib which help you to use SWFTools http://www.swftools.org/

SWFTools are GPL licensed and are described as "a collection of utilities for
working with Adobe Flash files"

Documentation available at http://php-swftools.readthedocs.org/

## Installation

It is recommended to install PHP-Swftools through
[Composer](http://getcomposer.org) :

```json
{
    "require": {
        "swftools/swftools": "~0.1.0"
    }
}
```

##Dependencies :

In order to use PHP SwfTools, you need to install SWFTools. Depending of your
configuration, please follow the instructions at
http://wiki.swftools.org/wiki/Installation.

##Main API usage :

```php
$file = new SwfTools\FlashFile(SwfTools\Binary\DriverContainer::create());

// Render the animation to a PNG file
$file->render('Animation.swf', 'renderedAnimation.png');

// List all embedded object found in the animation.
// Available object types are : Shapes, Fonts, PNGs, JPEGs, Frames, MovieClip
foreach($File->listEmbeddedObjects('Animation.swf') as $embeddedObject) {
    echo sprintf("found an object type %s with id %d\n", $embeddedObject->getType(), $embeddedObject->getId());
}

// Extract embedded Object #1
$file->extractEmbedded(1, 'Animation.swf', 'Object1.png');

// Extract the first embedded image found
$file->extractFirstImage('Animation.swf', 'renderedAnimation.jpg');
```

##Setting timeout

PHPSwfTools uses underlying processes to execute commands. You can set a timeout
to prevent these processes to run more than a defined duration.

To disable timeout, set it to `0` (default value).

```php
$file = new SwfTools\FlashFile(SwfTools\Binary\DriverContainer::create(
    'timeout' => 0,
));
```

##Using various binaries versions

PHPSwfTools uses ``swfextract`` an ``swfrender`` provided by SWFTools. If you
want to specify the path to the binary you wnat to use, you can add
configuration :

```php
$file = new SwfTools\FlashFile(SwfTools\Binary\DriverContainer::create(
    'pdf2swf.binaries'    => '/opt/local/swftools/bin/pdf2swf',
    'swfrender.binaries'  => '/opt/local/swftools/bin/swfrender',
    'swfextract.binaries' => '/opt/local/swftools/bin/swfextract',
));
```

## Silex Service Provider

PHP-Swtools provides a [Silex](http://silex.sensiolabs.org) service provider.
Every option is optional, use them depending of your configuration. By default,
PHP-Swftools will try to find the executable in the environment PATH and timeout
is set to 0 (no timeout).

```php
$app = new Silex\Application();
$app->register(new SwfTools\SwfToolsServiceProvider(), array(
    'swftools.configuration' => array(
        'pdf2swf.binaries'    => '/opt/local/swftools/bin/pdf2swf',
        'swfrender.binaries'  => '/opt/local/swftools/bin/swfrender',
        'swfextract.binaries' => '/opt/local/swftools/bin/swfextract',
        'timeout'    => 300,
    ),
    'swftools.logger' => $app->share(function (Application $app) {
        return $app['monolog'];
    });
));

$app['swftools.flash-file']->render('file.swf', 'output.jpg');
$app['swftools.pdf-file']->toSwf('output.swf');
```

##License

PHPSwftools are released under MIT License http://opensource.org/licenses/MIT

See LICENSE file for more information
