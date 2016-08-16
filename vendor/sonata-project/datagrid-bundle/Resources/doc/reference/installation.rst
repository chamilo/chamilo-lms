.. index::
    single: Installation
    single: Configuration

Installation
============

To begin, add the dependent bundles to the vendor/bundles directory. Add the following lines to the file deps:

.. code-block:: bash

    php composer.phar require sonata-project/datagrid-bundle

Now, add the bundle to the kernel:

.. code-block:: php

    <?php

    // app/AppKernel.php

    public function registerbundles()
    {
        return array(
            // Vendor specifics bundles
            new Sonata\DatagridBundle\SonataDatagridBundle(),
        );
    }

Configuration
-------------

There is no configuration for now ...
