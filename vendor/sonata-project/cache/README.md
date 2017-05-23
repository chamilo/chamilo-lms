Cache
=====

[![Build Status](https://secure.travis-ci.org/sonata-project/cache.png)](https://secure.travis-ci.org/#!/sonata-project/cache)

Cache is a small library to handle cache backend, the library also handle counter.

### Installation using Composer

Add the dependency:

```bash
php composer.phar require sonata-project/cache
```

If asked for a version, type in 'dev-master' (unless you want another version):

```bash
Please provide a version constraint for the sonata-project/cache requirement: dev-master
```

### Cache Usage

```php
<?php

use Sonata\Cache\Adapter\Cache\PRedisCache;

$adapter = PRedisCache(array(
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 42
));

$keys = array(
    'objectId' => 10
);

$adapter->set($keys, "MyValue", 86400);

$cacheElement = $adapter->get($keys);

$cacheElement->getData(); // MyValue

```

### Counter Usage

```php
<?php

use Sonata\Cache\Adapter\Counter\PRedisCounter;

$adapter = PRedisCounter(array(
    'host'     => '127.0.0.1',
    'port'     => 6379,
    'database' => 42
));


$counter = $adapter->increment("mystats");

// $counter is a Counter object
$counter->getValue(); // will return 1 if the counter is new

$counter = $adapter->increment($counter, 10);

$counter->getValue(); // will return 11

```

### Google Groups

For questions and proposals you can post on this google groups

* [Sonata Users](https://groups.google.com/group/sonata-users): Only for user questions
* [Sonata Devs](https://groups.google.com/group/sonata-devs): Only for devs

