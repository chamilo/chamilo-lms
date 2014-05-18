Installation
============

* Add ``SonataCoreBundle`` to your ``vendor/bundles`` directory with the deps file::

.. code-block:: json

    //composer.json
    "require": {
    //...
        "sonata-project/core-bundle": "~2.2@dev",
    //...
    }


* Add ``SonataCoreBundle`` to your application kernel::

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

* Create a configuration file ``sonata_core.yml`` with this content::

.. code-block:: yaml

    sonata_core: ~

* Update the ``config.yml`` with the new resource to import::

.. code-block:: yaml

    imports:
        #...
        - { resource: sonata_core.yml }
