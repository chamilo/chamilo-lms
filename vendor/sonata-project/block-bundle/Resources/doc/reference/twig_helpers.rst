Twig Helpers
============

Render a block from its instance

.. code-block:: jinja

    {{ sonata_block_render(block) }}

Render by providing the block's type and options

.. code-block:: jinja

    {{ sonata_block_render({ 'type': 'sonata.block.service.rss' }, {
        'title': 'Sonata Project\'s Feeds',
        'url': 'http://sonata-project.org/blog/archive.rss'
    }) }}

Render by providing the block's cache options

.. code-block:: jinja

    {{ sonata_block_render(block, {
        'use_cache': use_cache,
        'extra_cache_key': extra_cache_key
    }) }}

Render a block by calling an event

.. code-block:: jinja

    {{ sonata_block_render_event('node.comment', {
        'target': post
    }) }}

review the events section for more information: :doc:`events`