.. index::
    single: Advanced
    single: Usage
    single: Cache
    single: Context

Advanced usage
==============

This happens when a block is rendered:

* A block is loaded based on the configuration passed to ``sonata_block_render``,
* If caching is configured, the cache is checked
* If cache exists and has content, the content is returned
* Each block model also has a block service, and its ``execute`` method is caled:

  * You can logic into block service's ``execute`` method, like in a controller,
  * It renders a template,
  * It returns a `Response` object.

Cache
-----

The ``SonataBlockBundle`` is integrated with the SonataCacheBundle_, which provides several caching solutions.
Have a look at the available adapters in the SonataCacheBundle_ to see all options.

Block Context Manager - context cache
-------------------------------------

When using ``Varnish``, ``Ssi`` or ``Js`` cache, the settings passed here are lost:

.. code-block:: jinja

    {{ sonata_block_render({ 'type': 'sonata.block.service.rss' }, {
        'title': 'Sonata Project\'s Feeds',
        'url': 'https://sonata-project.org/blog/archive.rss'
    }) }}

Taking ``Js`` as example, a URL is generated and added to some javascript. This javascript is injected into the page. When the page is loaded, the javascript calls the URL to retrieve the block content.

The default ``BlockContextManager`` automatically adds settings passed from the template to the ``extra_cache_keys`` with the key ``context``.
This allows cache adapters to rebuild a ``BlockContext`` if implemented.

Still in the ``Js`` example, the cache keys can be added to the URL as query parameters.
When the URL is called from the javascript, the cache adapter handles the request. It retrieves the settings from the ``context`` parameter and passes it to the ``BlockContextManager`` while creating a new ``BlockContext``.
This ensures the ``BlockContext`` is the same as when it was created from a template helper call without cache enabled.

.. note::

    The settings are exposed because they are added to the URL as parameters, secure the URL if needed.

Block loading
-------------

Block models are loaded by a chain loader. You should be able to add your own loader by tagging a service with ``sonata.block.loader"`` and implementing ``Sonata\BlockBundle\Block\BlockLoaderInterface`` in the loader class.

Empty block
-----------

By default, the loader interface expects the exception ``Sonata\BlockBundle\Exception\BlockNotFoundException`` if a block is not found.
Return an empty block from your loader class if the default behaviour for your blocks is to always return content.

.. _SonataCacheBundle: https://github.com/sonata-project/SonataCacheBundle