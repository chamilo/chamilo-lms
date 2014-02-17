Tacker
======

  "A worker who fastens things by tacking them (as with tacks or by spotwelding)"

Tacker is a config component that binds Symfony Config together into
a reuseable config loader for Pimple.

Features
--------

Works with ``yml``, ``ini``, ``json``, and ``php``.

* Inherit configurations files by using a special ``@import``
* Mix different configuration formats while using ``@import``
* Read configuration files are cached if a ``cache_dir`` is set and ``debug`` is set to false.
* Simple for all users of Pimple such as Silex and Flint.
* Only depends on Symfony Config.

Install
-------

Use composer to install this beautiful library.

.. code-block:: json

  {
      "require" : {
          "flint/tacker" : "~1.0"
      }
  }

Getting Started
---------------

.. code-block:: php

    <?php

    use Symfony\Component\Config\FileLocator;
    use Tacker\Loader\JsonFileLoader;
    use Tacker\Loader\CacheLoader;
    use Tacker\Loader\NormalizerLoader;
    use Tacker\Normalizer\ChainNormalizer;
    use Tacker\Configurator;

    $resources = new ResourceCollection;
    $loader = new JsonFileLoader(new FileLocator, $resources);

    // add CacheLoader and NormalizerLoader
    $loader = new CacheLoader(new NormalizerLoader($loader, new ChainNormalizer), $resources);

    // load just the parameters
    $parameters = $loader->load('config.{ini,json,yml}');

    // or use cofigurator with pimple
    $configurator = new Configurator($loader);
    $configurator->configure(new Pimple, 'config.json');

Inheriting
~~~~~~~~~~

All of the loaders supports inherited configuration files. Theese are supported through a special ``@import`` key.
``@import`` can contain a single string like ``config.json`` or an array of configuration files to load. In yaml
it would look something like:

.. code-block:: yaml

    @import:
        - config.json
        - another.yaml
        - third.ini

.. note::

    ``IniFileLoader`` does only support inheriting a single file, this is a limitation of the ini format.

Replacing Values
~~~~~~~~~~~~~~~~

Tacker comes with support for replacing values from environment value and/or Pimple services.

For environment variables it matches ``#CAPITALIZED_NAME#`` and for Pimple it matches ``%service.name%``.

.. code-block:: php

  <?php
  
  use Tacker\Normalizer\EnvironmentNormalizer;
  use Tacker\Normalizer\PimpleNormalizer;
  use Tacker\Normalizer\ChainNormalizer;
  
  $normalizer = new ChainNormalizer(array(
      new EnvironmentNormalizer,
      new PimpleNormalizer(new Pimple),
  ));

Tests
-----

Tacker is spec tested with PhpSpec. You run the tests with:

.. code-block:: bash

  $ ./vendor/bin/phpspec run
