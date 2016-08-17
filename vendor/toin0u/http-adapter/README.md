HttpAdapter
===========

This PHP 5.3+ library provides you simple HTTP adapters.

[![Build Status](https://secure.travis-ci.org/toin0u/HttpAdapter.png)](http://travis-ci.org/toin0u/HttpAdapter)
[![Coverage Status](https://coveralls.io/repos/toin0u/HttpAdapter/badge.png?branch=master)](https://coveralls.io/r/toin0u/HttpAdapter)


Installation
------------

This library can be found on [Packagist](https://packagist.org/packages/toin0u/http-adapter).
The recommended way to install this is through [composer](http://getcomposer.org).

Run these commands to install composer, the library and its dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar require toin0u/http-adapter:@stable
```

Usage
-----

Currently, these following adapters are available:

* `CurlHttpAdapter` to use [cURL](http://php.net/manual/book.curl.php).
* `BuzzHttpAdapter` to use [Buzz](https://github.com/kriswallsmith/Buzz), a lightweight PHP 5.3 library for
issuing HTTP requests.
* `GuzzleHttpAdapter` to use [Guzzle](https://github.com/guzzle/guzzle), PHP 5.3+ HTTP client and framework
for building RESTful web service clients.
* `ZendHttpAdapter` to use [Zend Http Client](http://framework.zend.com/manual/2.0/en/modules/zend.http.client.html).
* `SocketHttpAdapter` to use a [socket](http://www.php.net/manual/function.fsockopen.php).


Credits
-------

* [Antoine Corcy](https://twitter.com/toin0u)
* [All contributors](https://github.com/toin0u/HttpAdapter/contributors)


Acknowledgment
--------------

* [The almost missing Geocoder PHP library.](http://geocoder-php.org/)


Support
-------

[Please open an issues in github](https://github.com/toin0u/HttpAdapter/issues)


License
-------

HttpAdapter is released under the MIT License. See the bundled
[LICENSE](https://github.com/toin0u/HttpAdapter/blob/master/LICENSE) file for details.
