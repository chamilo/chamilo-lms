.. index::
    single: Events
    single: Block

Events
======

Sometime, you might want to create an area where a block can be added, depending on some external settings. A good example is
a `Comment mechanism`. You might want to create a ``CommentBundle`` to render a `comment thread` on different pages. The comment area can used `Disqus <http://disqus.com>`_ or your own solution.
As part of a full stack solution, you don't know which solution is going to be used. However, you know where the comment area will be located.

The `Event mechanism` implemented in the ``SonataBlockBundle`` tries to address this situation, and to provide a clean syntax:

.. code-block:: jinja

    {# post.twig.html #}
    <h1>{{ post.title }}</h1>
    <div> {{ post.message }} </div>

    {{ sonata_block_render_event('blog.comment', { 'target': post }) }}

The `Twig` helper will dispatch a ``BlockEvent`` object where services can add ``BlockInterface``. Once the event is processed, the helper will render the available blocks.
If there is no block, then the helper will return an empty string.

Implementation
~~~~~~~~~~~~~~

You can register a service to listen to the service ``blog.comment``. The actual name for the ``EventDispatcher`` must be prefixed by ``sonata.block.event``.
So, the current the name will be ``sonata.block.event.blog.comment``.

.. configuration-block::

    .. code-block:: xml

        <service id="discus.comment" class="Sonata\CommentBundle\Event\Discus">
            <tag name="kernel.event_listener" event="sonata.block.blog.comment" method="onBlock"/>
        </service>

    .. code-block:: yaml

        services:
            discus.comment:
                class: Sonata\CommentBundle\Event\Discus"
                tags:
                    - { name: kernel.event_listener, event: sonata.block.blog.comment, method: onBlock}

The `event listener` must return a ``BlockInterface`` so the rendering workflow will work properly.

.. code-block:: jinja

    <?php

    use Sonata\BlockBundle\Model\Block;

    class Discus
    {
        /**
         * @param  BlockEvent
         *
         * @return BlockInterface
         */
        public function onBlock(BlockEvent $event)
        {
            $block = new Block();
            $block->setId(uniqid()); // set a fake id
            $block->setSettings($event->getSettings());
            $block->setType('sonata.comment.block.discus');

            $event->addBlock($block);
        }
    }

And that's it! Of course, this example supposes that you have a ``BlockServiceInterface``, which can handle the type ``sonata.comment.block.discus``.

Profiler Information
~~~~~~~~~~~~~~~~~~~~

If an event is available in the current page, a ``*`` will appear next to the ``blocks`` label in the profiler toolbar.
In the following schema, you have 3 events and 1 generated block:

.. figure:: ../images/block_profiler.png
   :align: center
   :alt: Block profiler with events
   :width: 500

You can retrieve event's name in the block panel. The panel includes the event's name and the different listeners available and
the generated blocks (if any).

   .. figure:: ../images/block_profiler_event.png
      :align: center
      :alt: Block profiler with events
      :width: 500
