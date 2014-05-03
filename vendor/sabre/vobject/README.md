SabreTooth VObject library
==========================

The VObject library allows you to easily parse and manipulate [iCalendar](https://tools.ietf.org/html/rfc5545)
and [vCard](https://tools.ietf.org/html/rfc6350) objects using PHP.
The goal of the VObject library is to create a very complete library, with an easy to use API.

This project is a spin-off from [SabreDAV](http://code.google.com/p/sabredav/), where it has
been used for several years. The VObject library has 100% unittest coverage.

Build status
------------

| branch | status |
| ------ | ------ |
| master | [![Build Status](https://travis-ci.org/fruux/sabre-vobject.png?branch=master)](https://travis-ci.org/fruux/sabre-vobject) |
| 2.1    | [![Build Status](https://travis-ci.org/fruux/sabre-vobject.png?branch=2.1)](https://travis-ci.org/fruux/sabre-vobject) |
| 2.0    | [![Build Status](https://travis-ci.org/fruux/sabre-vobject.png?branch=2.0)](https://travis-ci.org/fruux/sabre-vobject) |


Installation
------------

VObject requires PHP 5.3, and should be installed using composer.
The general composer instructions can be found on the [composer website](http://getcomposer.org/doc/00-intro.md composer website).

After that, just declare the vobject dependency as follows:

```
"require" : {
    "sabre/vobject" : "~3.1"
}
```

Then, run `composer.phar update` and you should be good.

Usage
-----

* [3.x documentation](doc/usage_3.md)
* [2.x documentation](doc/usage_2.md)
* [Migrating from 2.x to 3.x](doc/MigratingFrom2to3.md)

Made at fruux
-------------

This library is being developed by [fruux](https://fruux.com/). Drop us a line for commercial services or enterprise support.
