<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('sonata_block');

        $node
            ->fixXmlConfig('default_context')
            ->fixXmlConfig('template')
            ->fixXmlConfig('block')
            ->fixXmlConfig('block_by_class')
            ->children()
                ->arrayNode('profiler')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('container_type', 'container_types')
                    ->children()
                        ->scalarNode('enabled')->defaultValue('%kernel.debug%')->end()
                        ->scalarNode('template')->defaultValue('SonataBlockBundle:Profiler:block.html.twig')->end()
                        ->arrayNode('container_types')
                            ->isRequired()
                            // add default value to well know users of BlockBundle
                            ->defaultValue(array('sonata.block.service.container', 'sonata.page.block.container', 'cmf.block.container', 'cmf.block.slideshow'))
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('default_contexts')
                    ->isRequired()
                    ->prototype('scalar')->end()
                ->end()

                ->scalarNode('context_manager')->defaultValue('sonata.block.context_manager.default')->end()
                ->arrayNode('http_cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('handler')->defaultValue('sonata.block.cache.handler.default')->end()
                        ->booleanNode('listener')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('block_base')->defaultValue(null)->end()
                        ->scalarNode('block_container')->defaultValue(null)->end()
                    ->end()
                ->end()

                ->arrayNode('blocks')
                    ->info('configuration per block service')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->fixXmlConfig('context')
                        ->fixXmlConfig('setting')
                        ->children()
                            ->arrayNode('contexts')
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('cache')->defaultValue('sonata.cache.noop')->end()
                            ->arrayNode('settings')
                                ->info('default settings')
                                ->useAttributeAsKey('id')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('exception')
                                ->children()
                                    ->scalarNode('filter')  ->defaultValue(null)->end()
                                    ->scalarNode('renderer')->defaultValue(null)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('menus')
                    ->info('KNP Menus available in sonata.block.menu block configuration')
                    ->useAttributeAsKey('id')
                    ->prototype('scalar')->end()
                ->end()

                ->arrayNode('blocks_by_class')
                    ->info('configuration per block class')
                    ->useAttributeAsKey('class')
                    ->prototype('array')
                        ->fixXmlConfig('setting')
                        ->children()
                            ->scalarNode('cache')->defaultValue('sonata.cache.noop')->end()
                            ->arrayNode('settings')
                                ->info('default settings')
                                ->useAttributeAsKey('id')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('exception')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('filter')
                    ->fixXmlConfig('renderer')
                    ->children()
                        ->arrayNode('default')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('filter')  ->defaultValue('debug_only')->end()
                                ->scalarNode('renderer')->defaultValue('throw')->end()
                            ->end()
                        ->end()

                        ->arrayNode('filters')
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                            ->defaultValue(array(
                                'debug_only'             => 'sonata.block.exception.filter.debug_only',
                                'ignore_block_exception' => 'sonata.block.exception.filter.ignore_block_exception',
                                'keep_all'               => 'sonata.block.exception.filter.keep_all',
                                'keep_none'              => 'sonata.block.exception.filter.keep_none',
                            ))
                        ->end()
                        ->arrayNode('renderers')
                            ->useAttributeAsKey('id')
                            ->prototype('scalar')->end()
                            ->defaultValue(array(
                                'inline'                 => 'sonata.block.exception.renderer.inline',
                                'inline_debug'           => 'sonata.block.exception.renderer.inline_debug',
                                'throw'                  => 'sonata.block.exception.renderer.throw',
                            ))
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
