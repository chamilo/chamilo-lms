.. index::
    single: Prototype

Rapid Prototyping with BlockBundle
==================================

Before starting to code a project, sometimes you need to integrate HTML into valid `Twig templates`. This work can be done by an external team. However, at some point, the work needs to be merged and this is where issues might occur. Let's see what can we do with ``Symfony2`` and ``BlockBundle`` to make that work easier.

The Symfony Template Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The TemplateController_ is a native Symfony controller that can be used to render a template from a route definition, this is a pretty nice feature when you start a project from templates. You can then generate all routes for your application without having the need to generate all the required controllers.

.. code-block:: yaml

    acme_privacy:
        path: /privacy
        defaults:
            _controller: FrameworkBundle:Template:template
            template:    'AcmeBundle:Static:privacy.html.twig'

The Template Block Service
~~~~~~~~~~~~~~~~~~~~~~~~~~

At this point, you might want to reuse some elements inside different templates. You can use native `Twig` features: ``block``, ``use`` or ``include``. You can also use the ``render`` function from the ``Symfony2`` framework. The first solution is not very good on the long run as some elements might require some extra data to be rendered, so you will need a dedicated controller to render these elements (the latter solution). Using the ``render`` function comes with a cost: a sub request is generated, all related events are dispatched, and you are limited in the caching strategy.

This overhead is not always required just to render an area and you might want to have a fine control over the caching strategy. This is where the ``TemplateBlockService`` can be an excellent complement to the TemplateController_.

You start integrating your HTML with the ``sonata.block.service.template`` block and once you need to use the block you can just update the ``sonata_block_render`` call with the correct service name and the valid settings.

The usage is very simple:

.. code-block:: jinja

    {{ sonata_block_render({ 'type': 'sonata.block.service.template' }, {
        'template': 'SonataDemoBundle:Block:myblock.html'
    }) }}

Example
~~~~~~~

The main template might look like:

.. code-block:: jinja

    <!DOCTYPE html>
    <html>
        <body>
            <head>
                <link rel="stylesheet" href="{{ asset('css/default.css') }}" type="text/css" media="all" />
                <script src="{{ asset('js/jquery-1.8.3.js') }}" type="text/javascript"></script>
            </head>

            <div class="container">
                {{ sonata_block_render({ 'type': 'sonata.block.service.template' }, {
                    'template': 'MyMenuBundle:Block:menu.twig.html'
                }) }}

                <div class="col-4">
                    {{ sonata_block_render({ 'type': 'sonata.block.service.template' }, {
                        'template': 'MyMenuBundle:Block:navigation.twig.html'
                    }) }}
                </div>

                <div class="col-6">
                    {{ sonata_block_render({ 'type': 'sonata.block.service.template' }, {
                        'template': 'MyMenuBundle:Block:content.twig.html'
                    }) }}
                </div>
            </div>
            <!-- monitoring:3e9fda56df2cdd3b039f189693ab7844fbb2d4f6 -->
        </body>
    </html>

.. _TemplateController: http://symfony.com/doc/current/cookbook/templating/render_without_controller.html
