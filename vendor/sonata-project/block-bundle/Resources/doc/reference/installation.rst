.. index::
    single: Installation
    single: Configuration

Installation
============

To begin, add the dependent bundles to the vendor/bundles directory. Add the following lines to the deps file:

.. code-block:: bash

    php composer.phar require sonata-project/block-bundle

Now, add the bundle to the kernel:

.. code-block:: php

    <?php

    // app/AppKernel.php

    public function registerbundles()
    {
        return array(
            // Dependency (check that you don't already have this line)
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            // Vendor specifics bundles
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
        );
    }

Some features provided by this Bundle require the ``SonataAdminBundle``.
Please add an explicit required dependency to your project's `composer.json` to
the ``SonataAdminBundle`` with the version listed in the suggestion of this Bundle.

Configuration
-------------

To use the ``BlockBundle``, add the following lines to your application configuration file:

.. code-block:: yaml

    # app/config/config.yml

    sonata_block:
        default_contexts: [sonata_page_bundle]
        blocks:
            sonata.admin.block.admin_list:
                contexts:   [admin]

            #sonata.admin_doctrine_orm.block.audit:
            #    contexts:   [admin]

            sonata.block.service.text:
            sonata.block.service.rss:

            # Some specific block from the SonataMediaBundle
            #sonata.media.block.media:
            #sonata.media.block.gallery:
            #sonata.media.block.feature_media:
