UPGRADE FROM 2.1 to 2.2
=======================

    A block context is now implemented, see the change in BlockServiceInterface:

        * BlockServiceInterface

             /**
        -     * Returns the default settings link to the service
        +     * Define the default options for the block
              *
        -     * @return array
        +     * @param OptionsResolverInterface $resolver
              */
        -    public function getDefaultSettings();
        +    public function setDefaultSettings(OptionsResolverInterface $resolver);


    So to update, just use the ``OptionsResolverInterface`` API as the Form Component:

        -    public function getDefaultSettings()
        +    public function setDefaultSettings(OptionsResolverInterface $resolver)
             {
        -        return array(
        -            'url'     => false,
        -            'title'   => 'Insert the rss title'
        -        );
        +        $resolver->setDefaults(array(
        +            'url'      => false,
        +            'title'    => 'Insert the rss title',
        +            'template' => 'SonataBlockBundle:Block:block_core_rss.html.twig',
        +        ));
             }

    The template is now a mandatory parameter (which can be set to false), so the ``execute`` method should like:

        -    public function execute(BlockInterface $block, Response $response = null)
        +    public function execute(BlockContextInterface $blockContext, Response $response = null)
             {
        -        $settings = array_merge($this->getDefaultSettings(), $block->getSettings());
        -
        -        return $this->renderResponse('SonataBlockBundle:Block:block_core_text.html.twig', array(
        -            'block'     => $block,
        -            'settings'  => $settings
        +        return $this->renderResponse($blockContext->getTemplate(), array(
        +            'block'     => $blockContext->getBlock(),
        +            'settings'  => $blockContext->getSettings()
                 ), $response);
             }


    The twig template helper sonata_block_render has been updated:

        sonata_block_render(block, use_cache, extra_cache_key)

            => sonata_block_render(block, {'use_cache': use_cache, 'extra_cache_key': extra_cache_key})


    If you are using the page bundle you need to configure the ``context_manager`` section

        sonata_block:
            context_manager: sonata.page.block.context_manager