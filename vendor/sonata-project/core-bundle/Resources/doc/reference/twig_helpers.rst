.. index::
    double: Twig Helpers; Definition

Twig Helpers
============

sonata_slugify (deprecated)
---------------------------

This filter is deprecated. Install `cocur/slugify` and enable `CocurSlugifyBundle` https://github.com/cocur/slugify#symfony2 for using `slugify` filter.

Create a slug from a string:

.. code-block:: jinja

    {{ "my string"|sonata_slugify }}

Results in ::

    my-string

sonata_flashmessages_get and sonata_flashmessages_types
-------------------------------------------------------

See :doc:`flash_messages` for more information.

sonata_urlsafeid
----------------

Gets the identifiers of the object as a string that is safe to use in an url.

