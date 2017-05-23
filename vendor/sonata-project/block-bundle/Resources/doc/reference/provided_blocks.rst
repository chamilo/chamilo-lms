.. index::
    single: Block

Provided Blocks
===============

Some block services are already provided. You may use them or check out the code to get ideas on how to create your own.
You can also check this documentation: :doc:`your_first_block`.

EmptyBlockService
-----------------

The purpose of this block is to always return content, even on exceptions (``Sonata\BlockBundle\Exception\BlockNotFoundException``). See :doc:`Advanced Usage <advanced_usage>`.

TextBlockService
----------------

This block allows you to render anything you'd like. Be warned, the content you feed it with will be directly interpreted (which allows you to put in some HTML for instance).

Pretty straightforward, you need only to add the block service to your page and configure it with the content you'd like to see displayed in HTML.

RssBlockService
---------------

This block displays an RSS feed.

When you add this block, specify a title and an RSS URL. Then, the last messages from the RSS feed will be displayed in your block.

Base template is ``SonataBlockBundle:Block:block_core_rss.html.twig`` but you may of course override it.

MenuBlockService
----------------

This block service displays a KNP Menu.

Defining a menu could be done by inserting the ``sonata.block.menu`` tag.

.. configuration-block::

    .. code-block:: xml

        <service id="sonata.block.menu.main" class="Sonata\BlockBundle\Menu\MainMenu">
            <tag name="sonata.block.menu" />
        </service>

Upon configuration, you may set some rendering options (see KNP Doc for those).

Set ``cache_policy`` to private if this menu is dedicated to be in a user part.

A specific menu template is provided as well to render Bootstrap3's side menu, you may use it by setting the ``menu_template`` option to ``SonataBlockBundle:Block:block_side_menu_template.html.twig`` (see the implementation in SonataUserBundle or Sonata's e-commerce suite).

.. _KnpMenuBundle documentation: https://symfony.com/doc/current/bundles/KnpMenuBundle/index.html
