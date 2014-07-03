Installation
============

Base bundles
------------

This bundle is mainely dependant of the SonataJqueryBundle. So be sure you have install this bundle before start:

 * http://sonata-project.org/bundles/jquery/master/doc/reference/installation.html

Installation
------------

Retrieve the bundle with composer:

    php composer.phar require sonata-project/markitup-bundle --no-update

Register the new bundle into your AppKernel:

.. code-block:: php

  <?php
  // app/AppKernel.php
  public function registerBundles()
  {
      return array(
          // ...
          new Sonata\MarkItUpBundle\SonataMarkItUpBundle(),
          // ...
      );
  }

Next, publish the assets:

    php app/console assets:install web
