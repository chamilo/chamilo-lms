<?php

namespace Knp\Bundle\MarkdownBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('knp_markdown', 'array')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('parser')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service')->cannotBeEmpty()->defaultValue('markdown.parser.max')->end()
                    ->end()
                ->end()
                ->arrayNode('sundown')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('extensions')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('fenced_code_blocks')->defaultFalse()->end()
                                ->booleanNode('no_intra_emphasis')->defaultFalse()->end()
                                ->booleanNode('tables')->defaultFalse()->end()
                                ->booleanNode('autolink')->defaultFalse()->end()
                                ->booleanNode('strikethrough')->defaultFalse()->end()
                                ->booleanNode('lax_html_blocks')->defaultFalse()->end()
                                ->booleanNode('space_after_headers')->defaultFalse()->end()
                                ->booleanNode('superscript')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->arrayNode('render_flags')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('filter_html')->defaultFalse()->end()
                                ->booleanNode('no_images')->defaultFalse()->end()
                                ->booleanNode('no_links')->defaultFalse()->end()
                                ->booleanNode('no_styles')->defaultFalse()->end()
                                ->booleanNode('safe_links_only')->defaultFalse()->end()
                                ->booleanNode('with_toc_data')->defaultFalse()->end()
                                ->booleanNode('hard_wrap')->defaultFalse()->end()
                                ->booleanNode('xhtml')->defaultFalse()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
