.. index::
    double: Reference; Introduction
    single: Command line

Introduction
============

``SonataEasyExtendsBundle`` is a prototype for generating a valid bundle structure from a `Vendor` Bundle.
The tool is started with the simple command line: ``sonata:easy-extends:generate``.

The command line will generate:

* all required directories for one bundle (controller, config, doctrine, views, ...),
* the mapping and entity files from those defined in the CPB. The `SuperClass` must be prefixed by ``BaseXXXXXX``,
* the table name from the bundle name + entity name. For instance, ``blog__post``, where blog is the BlogBundle and Post the entity name.

You can optionally define a ``--dest`` option to the command with the target directory for the extended bundle creation.
By default, this is set to ``app`` but you should probably set it to ``src``.
