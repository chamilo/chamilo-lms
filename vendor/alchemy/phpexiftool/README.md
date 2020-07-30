# PHP-Exiftool

[![Build Status](https://secure.travis-ci.org/alchemy-fr/PHPExiftool.png?branch=master)](http://travis-ci.org/alchemy-fr/PHPExiftool)

This project is a fork of [phpexiftool/phpexiftool](https://github.com/phpexiftool/phpexiftool).

PHP Exiftool is an Object Oriented driver for Phil Harvey's Exiftool (see
http://www.sno.phy.queensu.ca/~phil/exiftool/).
Exiftool is a powerful library and command line utility for reading, writing
and editing meta information written in Perl.

PHPExiftool provides an intuitive object oriented interface to read and write
metadata.

You will find some example below.
This driver is not suitable for production, it is still under heavy development.

## Installation

The recommended way to install PHP-Exiftool is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "alchemy/phpexiftool": "^0.5.0"
    }
}
```

## Usage

### Exiftool Reader

A simple example : how to read metadata from a file:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use PHPExiftool\Reader;
use PHPExiftool\Driver\Value\ValueInterface;

$logger = new Logger('exiftool');
$reader = Reader::create($logger);

$metadataBag = $reader->files(__FILE__)->first();

foreach ($metadataBag as $metadata) {
    if (ValueInterface::TYPE_BINARY === $metadata->getValue()->getType()) {
        echo sprintf("\t--> Field %s has binary data" . PHP_EOL, $metadata->getTag());
    } else {
        echo sprintf("\t--> Field %s has value(s) %s" . PHP_EOL, $metadata->getTag(), $metadata->getValue()->asString());
    }
}
```

An example with directory inspection :

```php
use Monolog\Logger;
use PHPExiftool\Reader;
use PHPExiftool\Driver\Value\ValueInterface;

$logger = new Logger('exiftool');
$reader = Reader::create($logger);

$reader
  ->in(array('documents', '/Picture'))
  ->extensions(array('doc', 'jpg', 'cr2', 'dng'))
  ->exclude(array('test', 'tmp'))
  ->followSymLinks();

foreach ($reader as $data) {
    echo "found file " . $data->getFile() . PHP_EOL;

    foreach ($data as $metadata) {
        if (ValueInterface::TYPE_BINARY === $metadata->getValue()->getType()) {
            echo sprintf("\t--> Field %s has binary data" . PHP_EOL, $metadata->getTag());
        } else {
            echo sprintf("\t--> Field %s has value(s) %s" . PHP_EOL, $metadata->getTag(), $metadata->getValue()->asString());
        }
    }
}
```

### Exiftool Writer

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use PHPExiftool\Writer;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Driver\Tag\IPTC\ObjectName;
use PHPExiftool\Driver\Value\Mono;

$logger = new Logger('exiftool');
$writer = Writer::create($logger);

$bag = new MetadataBag();
$bag->add(new Metadata(new ObjectName(), new Mono('Pretty cool subject')));

$writer->write('image.jpg', $bag);
```

## License

Project licensed under the MIT License
