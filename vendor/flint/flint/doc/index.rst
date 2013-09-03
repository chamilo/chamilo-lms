Flint
=====

Flint is a microframework built on top of Silex. It tries to bridge the gap between Silex and
Symfony by bringing structure and conventions to Silex.

Getting started
---------------

To start a new project with Flint the easiest way is to use
`Composer <http://getcomposer.org>`__ and
`Flint-Skeleton <http://github.com/henrikbjorn/flint-skeleton>`__.

.. code-block:: bash

    $ php composer.phar create-project -s dev henrikbjorn/flint-skeleton my-flint-application

Or if you are migrating from a Silex project you can change your
``composer.json`` file to require Flint and change the Application class
that is used.

.. code-block:: bash

    $ php composer.phar require flint/flint:~1.0

.. code-block:: php

    <?php

    use Flint\Application;

    $application = new Application($rootDir, $debug);

It is recommended to subclass ``Flint\Application`` instead of using the
application class directly.

Controllers
-----------

Flint tries to make Silex more like Symfony. And by using closures it is
hard to seperate controllers in a logic way when you have more than a
couple of them. To make it better it is recommended to use classes and
methods for controllers. The basics is `explained
here <http://silex.sensiolabs.org/doc/usage.html#controllers-in-classes>`__
but Flint takes it further and allows the application to be injected
into a controller.

The first way to accomplish this is by implementing
``PimpleAwareInterface`` or extending ``PimpleAware``. This works
exactly `as described in
Symfony <http://symfony.com/doc/2.0/book/controller.html#the-base-controller-class>`__.
With the only exception that the property is called ``$pimple`` instead
of ``$container``.

.. code-block:: php

    <?php

    namespace Acme\Controller;

    use Flint\PimpleAware;

    class HelloController extends PimpleAware
    {
        public function indexAction()
        {
            return $this->pimple['twig']->render('Hello/index.html.twig');
        }
    }

Another way is to use a base controller which have convenience methods
for the most frequently used services. Theese methods can be seen in the
source code if you look at the implementation for
``Flint\Controller\Controller``.

.. code-block:: php

    <?php

    namespace Acme\Controller;

    use Flint\Controller\Controller;

    class HelloController extends Controller
    {
        public function indexAction()
        {
            return $this->render('Hello/index.html.twig');
        }
    }

Routing
-------

Because Flint replaces the url matcher used in Symfony with the full
router implementation a lot of new things is possible.

Caching is one of thoose things. It makes your application faster as it
does not need to register routes on every request. Together with loading
your routes from a configuration file like Symfony it will also bring
more structure to your application.

To enable caching you just need to point the router to the directory you
want to use and if it should cache or not. By default the ``debug``
parameter will be used as to determaine if cache should be used or not.

.. code-block:: php

    <?php

    // .. create a $app before this line
    $app['routing.options'] = array(
        'cache_dir' => '/my/cache/directory/routing',
    );

Before it is possible to use the full power of caching it is needed to
use configuration files because Silex will always call add routes via
its convenience methods ``get|post|delete|put``. Furtunately this is
baked right in.

.. code-block:: php

    <?php

    // .. create $app
    $app['routing.resource'] = 'config/routing.xml';

.. code-block:: xml

    <!-- config/routing.xml -->
    <?xml version="1.0" encoding="UTF-8" ?>
    <routes xmlns="http://symfony.com/schema/routing"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://symfony.com/schema/routing http://symfony.com/schema/routing/routing-1.0.xsd">

        <route id="homepage" pattern="/">
            <default key="_controller">Acme\\Controller\\DefaultController::indexAction</default>
        </route>
    </routes>

This will make the router load that resource by default. Here xml is
used as an example but ``php`` is also supported together with ``yml``
if ``Symfony\Component\Yaml\Yaml`` is autoloadable.

The benefit from doing it this way is of course they can be cached but
also it allows you to import routing files that are included in
libraries and even other Symfony bundles such as the
`WebProfilerBundle <https://github.com/symfony/webprofilerbundle>`__.
Also it will make it easier to generate routes from inside your views.

.. code-block:: jinja

    <a href="{{ app.router.generate('homepage') }}">Homepage</a>

This is also possible with Silex but with a more verbose syntax. The
syntax can be even more precise by using the twig functions that is
available in the Twig bridge for Symfony. To enable thoose add the twig
bridge to your composer file.

.. code-block:: json

    {
        "require" : {
            "symfony/twig-bridge" : "~2.0"
        }
    }

Now it is possible to use the functions inside your Twig templates.

.. code-block:: jinja

    <a href="{{ path('homepage') }}">Homepage</a>
    <a href="{{ url('homepage') }}">Homepage</a>

Default Parameters
------------------

The two contructor arguments ``$rootDir`` and ``$debug`` are also
registered on the application as parameters. This makes it easier for
services to add paths for caching, logs or other directories.

.. code-block:: php

    <?php

    $app = new Flint\Application(__DIR__, true);
    $app['debug'] === true;
    $app['root_dir'] === __DIR__;

Custom Error Pages
------------------

When finished a project or application it is the small things that
matter the most. Such as having a custom error page instead of the one
Silex provides by default. Also it can help a lost user navigate back.
Flint makes this possible by using the exception handler from Symfony
and a dedicated controller. Both the views and the controller can be
overrriden.

This will only work when debug is turned off.

To override the error pages the same logic is used as inside Symfony.
The logic is very well described `in their
documentation <http://symfony.com/doc/master/cookbook/controller/error_pages.html>`__.

Only difference from Symfony is the templates must be created inside
``views/Exception/`` directory. Inside the templates there is access to
``app`` which in turns gives you access to all of the services defined.

To override the controller used by the exception handler change the
``exception_controller`` parameter. This parameter will by default be
set to ``Flint\\Controller\\ExceptionController::showAction``.

.. code-block:: php

    <?php

    // .. create $app
    $app->inject(array(
        'exception_controller' => 'Acme\\Controller\\ExceptionController::showAction',
    ));

To see what parameter the controller action takes look at the one
provided by default. Normally it should not be overwritten as it already
gives a lot of flexibilty with the template lookup.


Pimple Console
--------------

Helper
~~~~~~

Flint have a helper that provides access to a pimple instance or in the case of Flint access to you application
object.

.. code-block:: php

    <?php

    class SomeCommand extends Command
    {
        public function execute(InputInterface $input, OutputInterface $output)
        {
            $pimple = $this->getHelperSet()->get('pimple');
        }
    }

To register the helper do this.

.. code-block:: php

    <?php

    $app = new Symfony\Component\Console\Application;
    $app->getHelperSet()->set(new Flint\Console\PimpleHelper($pimple));


Application
~~~~~~~~~~~

.. warning::
    
    This is deprecated and it is adviced to use ``Flint\Console\PimpleHelper`` instead.

``Flint\Console\Application`` is an extension of the base console
application shipped with Symfony. It gives access to Pimple in commands.

.. code-block:: php

    <?php

    namespace Application\Command;

    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class MyCommand extends \Symfony\Component\Console\Command\Command
    {

        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $pimple = $this->getApplication()->getPimple();
        }
    }

Configuration
-------------

Every application need to have some parameters configured based on environment or other parameters.
Flint comes with a ``Configurator`` which reads ``json`` files and sets them as parmeters on your application.

It is very easy to use:

.. code-block:: php

    <?php

    use Flint\Application;

    $app = new Application($rootDir, $debug);
    $app->configure('config.json');

    // Or use the service directly
    $app['configurator']->configure($app, 'app/config/prod.json');

The Configurator will replace placeholders marked with ``%my_parameter%`` with the corresponding parameter in your
application which in this instance would be ``$app['my_parameter']``. It will also replace placeholders marked as
``#my_env#`` with environment variables.

It is possible to inherit from a config file by using a special key ``@import`` and set its value to another file. The
loaded parameters from ``@import`` will have the lowest priority when merging the two files.

.. warning::

    When using Silex version 1.0.0 or earlier it is not possible to load configurations in the boot method. This is because
    when adding a listener to the `dispatcher` service it will get the routes and a bunch of other services which means it
    is too late.
