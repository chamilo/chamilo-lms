<?php

/*
 * This file is part of the Ivory CKEditor package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\CKEditorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Ivory CKEditor configuration.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = $this->createTreeBuilder();
        $treeBuilder
            ->root('ivory_ck_editor')
            ->children()
                ->booleanNode('enable')->defaultTrue()->end()
                ->scalarNode('base_path')->defaultValue('bundles/ivoryckeditor/')->end()
                ->scalarNode('js_path')->defaultValue('bundles/ivoryckeditor/ckeditor.js')->end()
                ->scalarNode('default_config')->end()
                ->append($this->createConfigsNode())
                ->append($this->createPluginsNode())
                ->append($this->createStylesNode())
                ->append($this->createTemplatesNode())
                ->append($this->createToolbarsNode())
            ->end();

        return $treeBuilder;
    }

    /**
     * Creates the configs node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition The configs node.
     */
    protected function createConfigsNode()
    {
        return $this->createNode('configs')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->useAttributeAsKey('name')
                ->prototype('variable')->end()
            ->end();
    }

    /**
     * Creates the plugins node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition The plugins node.
     */
    protected function createPluginsNode()
    {
        return $this->createNode('plugins')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('path')->end()
                    ->scalarNode('filename')->end()
                ->end()
            ->end();
    }

    /**
     * Creates the styles node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition The styles node.
     */
    protected function createStylesNode()
    {
        return $this->createNode('styles')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->prototype('array')
                    ->children()
                        ->scalarNode('name')->end()
                        ->scalarNode('element')->end()
                        ->arrayNode('styles')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('attributes')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Creates the templates node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition The templates node.
     */
    protected function createTemplatesNode()
    {
        return $this->createNode('templates')
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('imagesPath')->end()
                    ->arrayNode('templates')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('title')->end()
                                ->scalarNode('image')->end()
                                ->scalarNode('description')->end()
                                ->scalarNode('html')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Creates the toolbars node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition The toolbars node.
     */
    protected function createToolbarsNode()
    {
        return $this->createNode('toolbars')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('configs')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
                ->arrayNode('items')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * Creates a node.
     *
     * @param string $name The node name.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition The node.
     */
    protected function createNode($name)
    {
        return $this->createTreeBuilder()->root($name);
    }

    /**
     * Creates a tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder.
     */
    protected function createTreeBuilder()
    {
        return new TreeBuilder();
    }
}
