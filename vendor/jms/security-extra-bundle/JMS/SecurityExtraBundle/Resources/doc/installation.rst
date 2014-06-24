Installation
------------

1. Using Composer (recommended)
-------------------------------

To install JMSSecurityExtraBundle with Composer just add the following to your
`composer.json` file:

.. code-block :: js

    // composer.json
    {
        // ...
        require: {
            // ...
            "jms/security-extra-bundle": "dev-master"
        }
    }
    
.. note ::

    Please replace `dev-master` in the snippet above with the latest stable
    branch, for example ``1.0.*``.
    
Then, you can install the new dependencies by running Composer's ``update``
command from the directory where your ``composer.json`` file is located:

.. code-block :: bash

    php composer.phar update jms/security-extra-bundle
    
Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

.. code-block :: php

    <?php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\AopBundle\JMSAopBundle(),
        new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
        new JMS\DiExtraBundle\JMSDiExtraBundle($this),
        // ...
    );

2. Using the ``deps`` file (Symfony 2.0.x)
------------------------------------------

First, checkout a copy of the code. Just add the following to the ``deps`` 
file of your Symfony Standard Distribution:

.. code-block :: ini

    [JMSSecurityExtraBundle]
        git=https://github.com/schmittjoh/JMSSecurityExtraBundle.git
        target=/bundles/JMS/SecurityExtraBundle
        
    ; Dependencies:
    ;--------------
    [metadata]
        git=https://github.com/schmittjoh/metadata.git
        version=1.1.0 ; <- make sure to get 1.1, not 1.0
    
    ; see https://github.com/schmittjoh/JMSAopBundle/blob/master/Resources/doc/index.rst    
    [JMSAopBundle]
        git=https://github.com/schmittjoh/JMSAopBundle.git
        target=/bundles/JMS/AopBundle
    
    [cg-library]
        git=https://github.com/schmittjoh/cg-library.git
        
    ; This dependency is optional (you need it if you are using non-service controllers):
    ; see https://github.com/schmittjoh/JMSDiExtraBundle/blob/master/Resources/doc/index.rst
    [JMSDiExtraBundle]
        git=https://github.com/schmittjoh/JMSDiExtraBundle.git
        target=/bundles/JMS/DiExtraBundle
        
        
Then register the bundle with your kernel:

.. code-block :: php

    <?php

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new JMS\AopBundle\JMSAopBundle(),
        new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
        new JMS\DiExtraBundle\JMSDiExtraBundle($this),
        // ...
    );

Make sure that you also register the namespaces with the autoloader:

.. code-block :: php

    <?php

    // app/autoload.php
    $loader->registerNamespaces(array(
        // ...
        // ...
        'JMS'              => __DIR__.'/../vendor/bundles',
        'Metadata'         => __DIR__.'/../vendor/metadata/src',
        'CG'               => __DIR__.'/../vendor/cg-library/src',
        // ...
    ));

Now use the ``vendors`` script to clone the newly added repositories 
into your project:

.. code-block :: bash

    php bin/vendors install
