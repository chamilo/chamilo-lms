.. index::
    double: Twig Helpers; Definition

Twig Helpers
============

sonata_slugify
--------------

Create a slug from a string:

.. code-block:: jinja

    {{ "my string"|sonata_slugify }} => my-string

sonata_flashmessages_get and sonata_flashmessages_types
-------------------------------------------------------

See :doc:`flash_messages` for more information.

sonata_urlsafeid
----------------

Gets the identifiers of the object as a string that is safe to use in an url.

