.. index::
    single: Advanced
    single: Usage
    single: Cache
    single: Context

Advanced usage
==============

This happens when a block is rendered:

* A block is loaded based on the configuration passed to ``sonata_block_render``,
* If caching is configured, the cache is checked and content is returned if found,
* Each block model also has a block service, the execute method of it is called:

  * You can put here logic like in a controller,
  * It calls a template,
  * The result is a `Response` object.

Cache
-----

The ``SonataBlockBundle`` integrated with the `SonataCacheBundle`_ provides several caching solutions.
Have a look at the available adapters in the ``SonataCacheBundle`` to see all options.

Block Context Manager - context cache
-------------------------------------

When using `Varnish`, `Ssi` or `Js` cache, the settings passed here are lost:

.. code-block:: jinja

    {{ sonata_block_render({ 'type': 'sonata.block.service.rss' }, {
        'title': 'Sonata Project\'s Feeds',
        'url': 'http://sonata-project.org/blog/archive.rss'
    }) }}

Taking Js as example, an url is generated and added to some javascript. This javascript is injected in the page. When the page is loaded, the javascript calls the url to retrieve the block content.

The default ``BlockContextManager`` automatically adds settings passed from the template to the ``extra_cache_keys`` with the key ``context``.
This allows cache adapters to rebuild a ``BlockContext`` if implemented.

Still in the Js example, the cache keys can be added to the url as query parameters.
When the url is called from the javascript, the cache adapter handles the request. It retrieves the settings from the ``context`` parameter and passes it to the ``BlockContextManager`` while creating a new ``BlockContext``.
This ensures the ``BlockContext`` is the same as when it was created from a template helper call without cache enabled.

.. note::

    The settings are exposed because they are added to the url as parameters, secure the url if needed.

Block loading
-------------

Block models are loaded by a chain loader, add your own loader by tagging a service with ``sonata.block.loader"`` and implement ``Sonata\BlockBundle\Block\BlockLoaderInterface`` in the loader class.

Empty block
-----------

By default, the loader interface expects the exception ``Sonata\BlockBundle\Exception\BlockNotFoundException`` if a block is not found.
Return an empty block from your loader class if the default behaviour for your blocks is to always return content.

.. _`SonataCacheBundle`: https://github.com/sonata-project/SonataCacheBundle