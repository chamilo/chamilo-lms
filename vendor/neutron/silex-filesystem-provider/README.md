#Silex Filesystem Service Provider

[![Build Status](https://secure.travis-ci.org/romainneutron/Silex-Filesystem-Service-Provider.png?branch=master)](http://travis-ci.org/romainneutron/Silex-Filesystem-Service-Provider)

This is a [Silex Service Provider](http://silex.sensiolabs.org/doc/providers.html)
for Symfony [Filesystem Component](http://symfony.com/doc/master/components/filesystem.html).

##Installation

Add it using [composer](http://getcomposer.org/) :

```json
{
    "require": {
        "neutron/silex-filesystem-provider": "dev-master"
    }
}
```

##Usage

```php
use Silex\Application;
use Neutron\Silex\Provider\FilesystemServiceProvider;

$app = new Application();
// Register the filesystem
$app->register(new FilesystemServiceProvider());

// Just use it as any Silex service
if (!$app['filesystem']->exists('any-absolute-or-relative-path...')) {
	throw new \Namespace\PathNotFoundCustomException();
}
```

###Informations
Read more informations about the [Symfony2 Filesystem Component](http://symfony.com/doc/current/components/filesystem.html)

##License

This is released under the MIT license
