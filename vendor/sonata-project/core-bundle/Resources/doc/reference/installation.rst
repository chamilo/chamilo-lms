.. index::
    single: Installation

Installation
============

* Add ``SonataCoreBundle`` to your ``vendor/bundles`` directory with the `deps` file:

.. code-block:: json

    // composer.json

    "require": {
    //...
        "sonata-project/core-bundle": "~2.2@dev",
    //...
    }


* Add ``SonataCoreBundle`` to your application kernel:

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

* Create a configuration file ``sonata_core.yml`` with this content:

.. code-block:: yaml

    sonata_core: ~

* Update the ``config.yml`` with the new resource to import:

.. code-block:: yaml

    imports:
        #...
        - { resource: sonata_core.yml }

When using bootstrap, some widgets need to be wrapped in a special ``div`` element
depending on whether you are using the standard style for your forms or the
horizontal style.

If you are using the horizontal style, you will need to configure the
corresponding configuration node accordingly:

.. code-block:: yaml

    sonata_core:
        form_type: horizontal

Please note that if you are using the admin bundle, this is actually optional:
The core bundle extension will detect if the configuration node that deals with
the form style in the admin bundle is set and will configure the core bundle for you.
