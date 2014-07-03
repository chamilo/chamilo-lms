Installation
============

.. note ::

    This version is not compatible with Symfony 2.0.x anymore. Please use an
    older version of this bundle if you are still on the 2.0 series.

JMSDiExtraBundle can be conveniently installed via Composer. Just add the
following to your `composer.json` file:

.. code-block :: js

    // composer.json
    {
        // ...
        require: {
            // ...
            "jms/di-extra-bundle": "dev-master"
        }
    }

.. note ::

    Please replace `dev-master` in the snippet above with the latest stable
    branch, for example ``1.0.*``. Please check the tags on Github for which
    versions are available.

Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

.. code-block :: bash

    php composer.phar update

Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

.. code-block :: php

    <?php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\DiExtraBundle\JMSDiExtraBundle($this),
        new JMS\AopBundle\JMSAopBundle(),
        // ...
    );
