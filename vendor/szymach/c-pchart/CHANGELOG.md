# Changelog

## 1.x
1.0 Stable version with basic functionality.

1.1 Added factory service.

1.1.1 Changed chart loading via factory a bit (see class annotations).

1.1.2 Updated service class with Exception handling regarding missing / wrong class name.

1.1.3 The file with classes' constants is now loaded via Composer (thanks to ThaDafinser).

1.1.4 Fixed code-breaking typ (thanks to subtronic).

1.1.5 Added an option to hide the X axis or only it's values (thanks to julien-gm).

1.1.6 Added support for closures in formatting scale (thanks to funkjedi)

## 2.x
2.0 Updated all classes to PSR-2 standard, added typehinting where possible, updated
    annotations in methods to be as accurate as possible. Added Behat testing and
    restructed the namespaces into more sensible structure.

2.0.1 Documentation updates.

2.0.2 Changed license to GPL-3.0.

2.0.3 Bubble chart fix (thanks to rage28).

2.0.4 PHP 7.1 initial support, lowered minimal PHP version to 5.4.

2.0.5 CS fixes, removed old MIT license file.

2.0.6 A fix for PHP 7.1 (thanks to dehrk).

2.0.7 A fix for computing color alpha (thanks to dehrk).

2.0.8 Covered most basic functionality with tests, added a lot of documentation
      and a PHP 7.1 fix. Deprecated the `\CpChart\Factory\Factory` class.

## 3.x

3.0 Deleted the `\CpChart\Factory\Factory` class and everything related to it.
    Moved drawing and cache classes outside of `Chart` namespace.
    Moved barcode to a `Barcode` namespace.
    Moved `cache` and `resources` directories to library root.
    Renamed `resources\data` to `resources\barcode`.
