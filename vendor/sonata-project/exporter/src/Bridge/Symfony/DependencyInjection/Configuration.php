<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata_exporter');

        $rootNode
            ->children()
                ->arrayNode('exporter')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('default_writers')
                            ->defaultValue(['csv', 'json', 'xls', 'xml'])
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('writers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('csv')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('filename')
                                    ->defaultValue('php://output')
                                    ->info('path to the output file')
                                ->end()
                                ->scalarNode('delimiter')
                                    ->defaultValue(',')
                                    ->info('delimits csv values')
                                ->end()
                                ->scalarNode('enclosure')
                                    ->defaultValue('"')
                                    ->info('will be used when a value contains the delimiter')
                                ->end()
                                ->scalarNode('escape')
                                    ->defaultValue('\\')
                                    ->info('will be used when a value contains the enclosure')
                                ->end()
                                ->booleanNode('show_headers')
                                    ->defaultValue(true)
                                    ->info('add column names as the first line')
                                ->end()
                                ->booleanNode('with_bom')
                                    ->defaultValue(false)
                                    ->info('include the byte order mark')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('json')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('filename')
                                    ->defaultValue('php://output')
                                    ->info('path to the output file')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('xls')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('filename')
                                    ->defaultValue('php://output')
                                    ->info('path to the output file')
                                ->end()
                                ->booleanNode('show_headers')
                                    ->defaultValue(true)
                                    ->info('add column names as the first line')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('xml')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('filename')
                                    ->defaultValue('php://output')
                                    ->info('path to the output file')
                                ->end()
                                ->booleanNode('show_headers')
                                    ->defaultValue(true)
                                    ->info('add column names as the first line')
                                ->end()
                                ->scalarNode('main_element')
                                    ->defaultValue('datas')
                                    ->info('name of the wrapping element')
                                ->end()
                                ->scalarNode('child_element')
                                    ->defaultValue('data')
                                    ->info('name of elements corresponding to rows')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
