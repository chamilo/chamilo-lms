Mink Extension
==============

You can use Behat to describe anything, that you can describe in business
logic. It’s tools, gui applications, web applications. Most interesting part
is web applications. First, behavioral testing already exists in web world -
it’s called functional or acceptance testing. Almost all popular frameworks
and languages provide functional testing tools. Today we’ll talk about how to
use Behat for functional testing of web applications. `Mink <http://mink.behat.org>`_
is a tool exactly for that and this extension provides integration for it.

Basically, MinkExtension is an integration layer between Behat 2.5 and Mink 1.4+
and it provides:

* Additional services for Behat (``Mink``, ``Sessions``, ``Drivers``).
* ``Behat\MinkExtension\Context\MinkAwareInterface`` which provides ``Mink``
  instance for your contexts or subcontexts.
* Base ``Behat\MinkExtension\Context\MinkContext`` context which provides base
  step definitions and hooks for your contexts or subcontexts. Or it could be
  even used as subcontext on its own.

Installation
------------

This extension requires:

* Behat 2.5
* Mink 1.4+

Through PHAR
~~~~~~~~~~~~

You should first download 3 phar archives:

* `behat.phar <http://behat.org/downloads/behat.phar>`_ - Behat itself
* `mink.phar <http://behat.org/downloads/mink.phar>`_ - Mink framework
* `mink_extension.phar <http://behat.org/downloads/mink_extension.phar>`_
  - integration extension

After downloading and placing them into project directory, you need to
activate ``mink_extension.phar`` in your ``behat.yml``:

.. code-block:: yaml

    # behat.yml
    default:
      # ...
      extensions:
        mink_extension.phar:
          mink_loader: 'mink-VERSION.phar'
          base_url:    'http://example.com'
          goutte:      ~

Through Composer
~~~~~~~~~~~~~~~~

The easiest way to keep your suite updated is to use `Composer <http://getcomposer.org>`_:

1. Define dependencies in your ``composer.json``:

    .. code-block:: js

        {
            "require": {
                ...

                "behat/mink-extension": "*"
            }
        }

2. Install/update your vendors:

    .. code-block:: bash

        $ curl http://getcomposer.org/installer | php
        $ php composer.phar install

3. Activate extension by specifying its class in your ``behat.yml``:

    .. code-block:: yaml

        # behat.yml
        default:
          # ...
          extensions:
            Behat\MinkExtension\Extension:
              base_url:  'http://example.com'
              goutte:    ~

Usage
-----

After installing extension, there would be 6 usage options available for you:

1. Writing features with bundled steps only. In this case, you don't need to create
   ``bootstrap/`` folder or custom ``FeatureContext`` class - Behat will use default
   ``MinkContext`` by default. To see all available steps, run:

    .. code-block:: bash

        $ bin/behat -di

2. Subcontexting/extending ``Behat\MinkExtension\Context\RawMinkContext`` in your feature suite.
   This will give you ability to use preconfigured `Mink` instance altogether with some
   convenience methods:
   * ``getSession($name = null)``
   * ``assertSession($name = null)``
   ``RawMinkContext`` doesn't provide any hooks or definitions, so you can inherit from it
   in as many subcontexts as you want - you'll never get ``RedundantStepException``.

3. Extending ``Behat\MinkExtension\Context\MinkContext`` with one of your contexts.
   Exactly like previous option, but also provides lot of predefined step definitions out
   of the box. As this context provides step definitions and hooks, you can use it **only once**
   inside your feature context tree.

    .. code-block:: php

        <?php

        use Behat\MinkExtension\Context\MinkContext;

        class FeatureContext extends MinkContext
        {
            /**
             * @Then /^I wait for the suggestion box to appear$/
             */
            public function iWaitForTheSuggestionBoxToAppear()
            {
                $this->getSession()->wait(5000, "$('.suggestions-results').children().length > 0");
            }
        }

    .. warning::

        Keep in mind, that you can not have multiple step definitions with same regex.
        It will cause ``RedundantException``. So, you can inherit from ``MinkContext``
        only with one of your context/subcontext classes.

4. Subcontexting ``Behat\MinkExtension\Context\MinkContext`` in your main context.
   Exactly like previous option, but gives you ability to keep your main context
   class clean.

    .. code-block:: php

        <?php

        use Behat\MinkExtension\Context\RawMinkContext;
        use Behat\MinkExtension\Context\MinkContext;

        class FeatureContext extends RawMinkContext
        {
            public function __construct(array $parameters)
            {
                $this->useContext('mink', new MinkContext);
            }
        }

    .. note::

        Keep in mind, that you can not have multiple step definitions with same regex.
        It will cause ``RedundantException``. So, you can inherit from ``MinkContext``
        only with one of your context/subcontext classes.

    .. note::

        Here, we are also extending our main context from ``RawMinkContext`` class.
        This class doesn't provide any definitions or hooks - just helper methods
        for you to interact with Mink. It means, that you could extend ``RawMinkContext``
        with as many context classes in your suite as you want.

5. If you're on the php 5.4+, you can simply use ``Behat\MinkExtension\Context\MinkDictionary``
   trait inside your ``FeatureContext`` or any of its subcontexts. This trait will provide
   all the needed methods, hooks and definitions for you to start. You can use this trait **only
   once** inside your feature context tree.

    .. code-block:: php

        <?php

        use Behat\Behat\Context\BehatContext;
        use Behat\MinkExtension\Context\MinkDictionary;

        class FeatureContext extends BehatContext
        {
            use MinkDictionary;

            /**
             * @Then /^I wait for the suggestion box to appear$/
             */
            public function iWaitForTheSuggestionBoxToAppear()
            {
                $this->getSession()->wait(5000, "$('.suggestions-results').children().length > 0");
            }
        }

6. Implementing ``Behat\MinkExtension\Context\MinkAwareInterface`` with your context or its
   subcontexts.

There's common things between last 5 methods. In each of those, target context will implement
``setMink(Mink $mink)`` and ``setMinkParameters(array $parameters)`` methods. Those methods would
be automatically called **immediately after** each context creation before each scenario. And
this ``$mink`` instance will be preconfigured based on the settings you've provided in your
``behat.yml``.

Configuration
-------------

MinkExtension comes with flexible configuration system, that gives you
ability to configure Mink inside Behat to fulfil all your needs.

Drivers
~~~~~~~

First of all, there's drivers enabling configuration. MinkExtension comes
with support for 5 drivers out of the box:

* ``GoutteDriver`` - default headless driver. It is used by default, which means
  that if you didn't changed ``default_session`` (another parameter) - you should
  always enable it.  In order to enable it, modify your ``behat.yml`` profile:

    .. code-block:: yaml

        default:
            extensions:
                Behat\MinkExtension\Extension:
                    goutte: ~

.. Tips : HTTPS and self-signed certificate
In case you use Behat/Mink/Goutte to test your application, and want to test an
application secured with HTTPS, but with a self-signed certificate, you can use
the following parameters to avoid the validation error triggered by Guzzle :

  .. code-block:: yaml

    default:
      extensions:
        Behat\MinkExtension\Extension:
          goutte:
            guzzle_parameters:
              ssl.certificate_authority: false

* ``Selenium2Driver`` - default javascript driver. It is used by default for
  ``@javascript`` tagged scenarios, which means that if you didn't changed
  ``javascript_session`` (another parameter) - you should always enable it (only
  if you use ``@javascript`` scenarios, of course).  In order to enable it,
  modify your ``behat.yml`` profile:

    .. code-block:: yaml

        default:
            extensions:
                Behat\MinkExtension\Extension:
                    selenium2: ~

* ``SeleniumDriver`` - another javascript driver. You could use it by setting it
  in ``javascript_session`` to ``selenium`` and by marking scenarios as ``@javascript``
  or simply by marking scenarios with ``mink:selenium`` (no need to switch
  ``javascript_session`` in this case). In order to enable it, modify your ``behat.yml``
  profile:

    .. code-block:: yaml

        default:
            extensions:
                Behat\MinkExtension\Extension:
                    selenium: ~

* ``SahiDriver`` - another javascript driver. You could use it by setting it
  in ``javascript_session`` to ``sahi`` and by marking scenarios as ``@javascript``
  or simply by marking scenarios with ``mink:sahi`` (no need to switch
  ``javascript_session`` in this case). In order to enable it, modify your ``behat.yml``
  profile:

    .. code-block:: yaml

        default:
            extensions:
                Behat\MinkExtension\Extension:
                    sahi: ~

* ``ZombieDriver`` - zombie.js javascript headless driver. You could use it by setting it
  in ``javascript_session`` to ``zombie`` and by marking scenarios as ``@javascript``
  or simply by marking scenarios with ``mink:zombie`` (no need to switch
  ``javascript_session`` in this case). In order to enable it, modify your ``behat.yml``
  profile:

    .. code-block:: yaml

        default:
            extensions:
                Behat\MinkExtension\Extension:
                    zombie: ~

.. note::

    phar version of Mink comes bundles with all 5 drivers and you don't need to do
    anything except enabling them in order to use them.

    But if you're using Composer, you need to install drivers that you need first:

    - GoutteDriver - ``behat/mink-goutte-driver``
    - SeleniumDriver - ``behat/mink-selenium-driver``
    - WebDriver - ``behat/mink-selenium2-driver``
    - SahiDriver - ``behat/mink-sahi-driver``
    - ZombieDriver - ``behat/mink-zombie-driver``

.. note::

    All drivers share same API, which means that you could use multiple drivers
    for the same suite - which one fits your needs for concrete scenarios. Don't
    try to stick to single driver as there's simply no universal solution - every
    driver has its pros and cons.

Additional Parameters
~~~~~~~~~~~~~~~~~~~~~

There's other useful parameters, that you can use to configure your suite:

* ``base_url`` - if you're using relative paths in your ``*.feature`` files
  (and you should), then this option will define which url use as a basename
  for them.
* ``files_path`` - there's special step definition for file upload inputs
  usage. You can use relative paths in those steps. ``files_path`` defines
  base path in which Mink should search those relative files.
* ``show_cmd`` - there's special definition in MinkExtension, that saves
  currently opened page into temporary file and opens it with some browser
  utility (for debugging). This option defines command to be used for opening.
  For example: ``show_cmd: 'firefox %s'``.
* ``browser_name`` - metaoption, that defines which browser to use for Sahi,
  Selenium and Selenium2 drivers.
* ``default_session`` - defines default session (driver) to be used for all
  untagged scenarios. Could be any enabled driver name.
* ``javascript_session`` - defines javascript session (driver) (the one, which
  will be used for ``@javascript`` tagged scenarios). Could be any enabled driver
  name.
