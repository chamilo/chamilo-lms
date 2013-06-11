Icu Component
=============

Contains data of the ICU library.

The bundled resource files have the [resource bundle format version 2.*] [1],
which can be read using ICU 4.4 and later. Compatibility can be tested with the
test-compat.php script bundled in the Intl component:

    php path/to/Symfony/Component/Intl/Resources/bin/test-compat.php

You should not directly use this component. Use it through the API of the
[Intl component] [2] instead.

Resources
---------

You can run the unit tests with the following command:

    $ cd path/to/Symfony/Component/Icu/
    $ composer.phar install --dev
    $ phpunit

[1]: http://site.icu-project.org/design/data/res2
[2]: https://github.com/symfony/Intl
