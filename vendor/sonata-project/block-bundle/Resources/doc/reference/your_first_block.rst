.. index::
    single: Block
    single: Tutorial
    single: RSS Block

Your first block
================

This quick tutorial explains how to create a `RSS reader` block.

A `block service` is just a service which must implement the ``BlockServiceInterface`` interface. There is only one instance of a block service, however there are many block instances.

First namespaces
----------------

The ``BaseBlockService`` implements some basic methods defined by the interface.
The current RSS block will extend this base class. The others `use` statements are required by the interface and remaining methods.

.. code-block:: php

    <?php

    namespace Sonata\BlockBundle\Block;

    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\OptionsResolver\OptionsResolverInterface;

    use Sonata\BlockBundle\Model\BlockInterface;
    use Sonata\BlockBundle\Block\BlockContextInterface;

    use Sonata\AdminBundle\Form\FormMapper;
    use Sonata\AdminBundle\Validator\ErrorElement;

Default settings
----------------

A `block service` needs settings to work properly, so to ensure consistency, the service should define a ``setDefaultSettings`` method.
In the current tutorial, the default settings are:

* `URL`: the feed url,
* `title`: the block title,
* `template`: the template to render the block.

.. code-block:: php

    <?php
    public function setDefaultSettings(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'url'      => false,
            'title'    => 'Insert the rss title',
            'template' => 'SonataBlockBundle:Block:block_core_rss.html.twig',
        ));
    }

Form Edition
------------

The ``BlockBundle`` relies on the ``AdminBundle`` to manage form edition and keep a good consistency.

.. code-block:: php

    <?php
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $formMapper->add('settings', 'sonata_type_immutable_array', array(
            'keys' => array(
                array('url', 'url', array('required' => false)),
                array('title', 'text', array('required' => false)),
            )
        ));
    }

The validation is done at runtime through a ``validateBlock`` method. You can call any Symfony2 assertions, like:

.. code-block:: php

    <?php
    public function validateBlock(ErrorElement $errorElement, BlockInterface $block)
    {
        $errorElement
            ->with('settings.url')
                ->assertNotNull(array())
                ->assertNotBlank()
            ->end()
            ->with('settings.title')
                ->assertNotNull(array())
                ->assertNotBlank()
                ->assertMaxLength(array('limit' => 50))
            ->end();
    }

The ``sonata_type_immutable_array`` type is a specific `form type` which allows to edit an array.

Execute
-------

The next step is the `Execute` method. This method must return a ``Response`` object, which is used to render the block.

.. code-block:: php

    <?php

    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        // merge settings
        $settings = $blockContext->getSettings();

        $feeds = false;
        if ($settings['url']) {
            $options = array(
                'http' => array(
                    'user_agent' => 'Sonata/RSS Reader',
                    'timeout' => 2,
                )
            );

            // retrieve contents with a specific stream context to avoid php errors
            $content = @file_get_contents($settings['url'], false, stream_context_create($options));

            if ($content) {
                // generate a simple xml element
                try {
                    $feeds = new \SimpleXMLElement($content);
                    $feeds = $feeds->channel->item;
                } catch (\Exception $e) {
                    // silently fail error
                }
            }
        }

        return $this->renderResponse($blockContext->getTemplate(), array(
            'feeds'     => $feeds,
            'block'     => $blockContext->getBlock(),
            'settings'  => $settings
        ), $response);
    }

Template
--------

In this tutorial, the block template is very simple. We loop through feeds, or if none are available, an error message is displayed.

.. code-block:: jinja

    {% extends sonata_block.templates.block_base %}

    {% block block %}
        <h3 class="sonata-feed-title">{{ settings.title }}</h3>

        <div class="sonata-feeds-container">
            {% for feed in feeds %}
                <div>
                    <strong><a href="{{ feed.link}}" rel="nofollow" title="{{ feed.title }}">{{ feed.title }}</a></strong>
                    <div>{{ feed.description|raw }}</div>
                </div>
            {% else %}
                    No feeds available.
            {% endfor %}
        </div>
    {% endblock %}

Service
-------

We are almost done! Now, just declare the block as a service:

.. code-block:: xml

    <service id="sonata.block.service.rss" class="Sonata\BlockBundle\Block\Service\RssBlockService">
        <tag name="sonata.block" />
        <argument>sonata.block.service.rss</argument>
        <argument type="service" id="templating" />
    </service>

and add it to Sonata configuration:

.. code-block:: yaml

    # app/config/config.yml

    sonata_block:
        blocks:
            sonata.block.service.rss:
    #           cache: sonata.cache.memcached


