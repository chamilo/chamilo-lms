.. index::
    single: Installation

Installation
============

The easiest way to install ``SonataCoreBundle`` is to require it with Composer:

.. code-block:: bash

    $ php composer.phar require sonata-project/core-bundle

Alternatively, you could add a dependency into your ``composer.json`` file directly.

Now, enable the bundle in the kernel:

.. code-block:: php

    <?php
    // app/AppKernel.php

    public function registerBundles()
    {
        return array(
            // ...
            new Sonata\CoreBundle\SonataCoreBundle(),
            // ...
        );
    }

Configuration
=============

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml

        sonata_core: ~

When using bootstrap, some widgets need to be wrapped in a special ``div`` element
depending on whether you are using the standard style for your forms or the
horizontal style.

If you are using the horizontal style, you will need to configure the
corresponding configuration node accordingly:

.. configuration-block::

    .. code-block:: yaml

        # app/config/config.yml

        sonata_core:
            form_type: horizontal

Please note that if you are using the admin bundle, this is actually optional:
The core bundle extension will detect if the configuration node that deals with
the form style in the admin bundle is set and will configure the core bundle for you.
