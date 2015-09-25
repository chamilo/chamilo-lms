.. index::
    double: Reference; Installation

Installation
============

To begin, add the dependent bundle:

.. code-block:: bash

    php composer.phar require sonata-project/easy-extends-bundle

Next, be sure to enable the new bundle in your application kernel:

.. code-block:: php

  <?php

  // app/AppKernel.php

  public function registerBundles()
  {
      return array(
          // ...
          new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
          // ...
      );
  }