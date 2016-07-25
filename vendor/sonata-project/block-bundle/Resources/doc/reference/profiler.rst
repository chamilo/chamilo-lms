.. index::
    single: Profiler
    single: Debug

Profiler
========

``BlockBundle`` automatically adds the profiling of blocks in `debug` mode. It adds a new tab in the Symfony web debug toolbar which contains the number of blocks used on a page.
It also provides a panel with the list of all rendered blocks, their memory consumption and their rendering time.

If you want to disable the profiling or configure it, you may add one of the following options in the block configuration file:

.. code-block:: yaml

    # app/config/config.yml

    sonata_block:
        profiler:
            enabled:        "%kernel.debug%"
            template:       SonataBlockBundle:Profiler:block.html.twig
