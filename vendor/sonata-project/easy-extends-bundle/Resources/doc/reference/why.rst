.. index::
    double: Reference; Installation
    single: Doctrine
    single: Tour

Make your Symfony2 bundle extendable
====================================

.. note::

    This post is not part of the Symfony2 documentation; it is just a state about how things work now (end of 2010)
    between Doctrine2 and Symfony2. It is not a complaint about the architecture, it just exposes how I solve a
    recurrent problem I have.

Let’s have a quick Symfony2 and Doctrine tour
---------------------------------------------

A quick Doctrine tour:

* `Doctrine2` entities are plain PHP objects; there is no database layer information. An ``Comment::post_id`` property is part of the database layer and not part of the domain layer. So a ``comment`` entity will have a ``post`` property and not a ``post_id`` property.
* `Doctrine2` entities are mapped through mapping information: `yaml`, `xml`, `annotation` or php code. There is one mapping per class, so if `Blog` extends `SuperBlog`, which extends `SuperEntity`, you will have 3 classes and so 3 information mappings and you will be able to use 3 different tables to save these entities,
* A mapped entity is final (from Doctrine2 point of view), it cannot be extended unless you create a new mapping definition for the new child class,
* Each entity is linked to a `ClassMetadata` information which contains all the mapping information and the final class name,
* An entity can extend a `SuperClass`. A `SuperClass` is just a mapping definition, a `SuperClass` cannot be persisted.

A quick `Symfony2` bundle tour:

* There are two types of bundles:

    * Application Bundle (AB),
    * Vendor Bundle (VB), that should not be modified inside a project.
* The AB directory is where developers implement the project requirements,
* An AB can overwrite almost everything from a VB, example: you can redefine a VB template at the AB level.

A namespace tour:

* “a namespace is an abstract container providing context for the items” (`Source <http://en.wikipedia.org/wiki/Namespace>`_),
* An entity is defined by a namespace,
* A bundle is defined by a namespace,
* A VB and AB are defined with two different namespaces.

Let’s start to mix these points together
----------------------------------------

* If an AB bundle A wants to use an entity from a VB bundle B, the fully qualify namespace must be used,
* If a developer wants to add a new property into a VB entity, the developer needs to create a new child entity with a custom mapping.

At this point, you have 2 entities with 2 different namespaces. The VB bundle’s code refers to its own namespace to
instantiate the model, BUT ... how ... you just create a new entity. Your VB will be unable to use this new model ... too bad.

Can this problem be solved with the Alternate syntax?
-----------------------------------------------------

There is actually a start of a solution, the ``DoctrineBundle`` allows us to use an alternate syntax, ie (``BlogBundle:Blog`` instead of ``Bundle\BlogBundle\Entity\Blog``).
As you can guess this syntax only works for string, inside a query for instance.

So if you want to instantiate a new model, you need first to get the `ClassMetadata` instance, retrieve the class
name and create the model. It’s not really nice and creates a dependency to the class metadata.

Last issue, the entity’s mapping association required fully qualifies namespace: no alternate syntax. (I suppose,
this last point can be fixed).

At this point, we are stuck with no solution to fully extend a bundle. (Don’t take this for granted; this might
change in a near future, as Symfony2 is not complete yet)

A pragmatic way to solve this issue
-----------------------------------

The easiest way to solve this problem is to use global namespace inside your VB, the global namespace is the only
namespace allowed  ``Application\YourBundle\Entity``.

So, inside your mapping definition or inside your VB code, you will use one final namespace: ``problem solved``.
How to achieve this:

* Declare only SuperClass inside a VB, don’t use final entity,
* Call your entity ``BaseXXXX`` and make it abstract, change the properties from private to protected,
* The same goes for a repository,
* Always use ``Application\YourBundle\Entity\XXXX`` inside your code.

Of course, you need to create for each VB bundle:

* a valid structure inside the Application directory,
* a valid entity mapping definition,
* a model inside the entity folder.

The last part is quite inefficient without an efficient tool to generate for you this structure: ``EasyExtendsBundle`` to the rescue.

How to make your bundle easy extendable?
----------------------------------------

Mainly all you need is to follow instructions in previous paragraph:

* Declare you entity/repository as described above,
* Use your entity/repository as described above,
* Before generation you also need "skeleton" file that will describe AB entity. Skeleton file can either `xml` or `yml`. For fully working example see ``SonataMediaBundle``.

At last you can run:

.. code-block:: bash

    php app/console sonata:easy-extends:generate YourVBBundleName


.. note::

    Note that the `--dest` option allows you to choose the target directory, such as `src`. Default destination is `app/`.
