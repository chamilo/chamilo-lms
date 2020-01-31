# Package Versions

> This is a backport of `ocramius/package-versions` that support php 5.6, while `ocramius/package-versions` only support php 7+.
> 
It's a more recent version of samsonasik/package-versions

This utility provides quick and easy access to version information of composer dependencies.

This information is derived from the ```composer.lock``` file which is (re)generated during ```composer install``` or ```composer update```.

```php
$version = \PackageVersions\Versions::getVersion('muglug/package-versions');
var_dump($version); // 1.0.0@0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33

$version = \PackageVersions\Versions::getShortVersion('muglug/package-versions');
var_dump($version); // 1.0.0

$version = \PackageVersions\Versions::getMajorVersion('muglug/package-versions');
var_dump($version); // 1
```

[![Build Status](https://travis-ci.org/muglug/PackageVersions.svg?branch=master)](https://travis-ci.org/muglug/PackageVersions)
[![Downloads](https://img.shields.io/packagist/dt/muglug/package-versions.svg)](https://packagist.org/packages/muglug/package-versions)
[![Packagist](https://img.shields.io/packagist/v/muglug/package-versions.svg)](https://packagist.org/packages/muglug/package-versions)
[![Packagist Pre Release](https://img.shields.io/packagist/vpre/muglug/package-versions.svg)](https://packagist.org/packages/muglug/package-versions)

### Installation

```sh
composer require muglug/package-versions
```

It is suggested that you use a optimized composer autoloader in order to prevent
autoload I/O when accessing the `PackageVersions\Versions` API:

Therefore you should use `optimize-autoloader: true` in your composer.json:
```
...
    "config": {
        "optimize-autoloader": true
    },
...
```
see https://getcomposer.org/doc/06-config.md#optimize-autoloader

In case you manually generate your autoloader via the CLI use the `--optimize` flag:

```sh
composer dump-autoload --optimize
```

### Use-cases

This repository implements `PackageVersions\Versions::getVersion()` in such a way that no IO
happens when calling it, because the list of package versions is compiled during composer
installation.

This is especially useful when you want to generate assets/code/artifacts that are computed from
the current version of a certain dependency. Doing so at runtime by checking the installed
version of a package would be too expensive, and this package mitigates that.


