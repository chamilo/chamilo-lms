.. index::
    single: Twig
    single: Helpers
    single: Example
    single: Usage

Twig Helpers
============

``SonataBlockBundle`` provides several Twig helper functions to allow easier interaction with blocks:

Render a block from its instance:

.. code-block:: jinja

    {{ sonata_block_render(block) }}

Render a block  by providing the block's type and options:

.. code-block:: jinja

    {{ sonata_block_render({ 'type': 'sonata.block.service.rss' }, {
        'title': 'Sonata Project\'s Feeds',
        'url': 'https://sonata-project.org/blog/archive.rss'
    }) }}

Render a block by providing the block's cache options:

.. code-block:: jinja

    {{ sonata_block_render(block, {
        'use_cache': use_cache,
        'extra_cache_key': extra_cache_key
    }) }}

Render a block by calling an event:

.. code-block:: jinja

    {{ sonata_block_render_event('node.comment', {
        'target': post
    }) }}

.. note::

    Review the `Events` section for more information: :doc:`events`

Rendering a block related to javascripts and stylesheets for the current page implies the helpers to be called at the end of the page:

.. code-block:: jinja

    {{ sonata_block_include_stylesheets('screen', app.request.basePath) }}
    {{ sonata_block_include_javascripts('screen', app.request.basePath) }}

The ``app.request.basePath`` must be provided if your application is stored in a sub-folder.