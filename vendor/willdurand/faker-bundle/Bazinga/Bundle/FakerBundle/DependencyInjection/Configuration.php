<?php

/**
 * This file is part of the FakerBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Bazinga\Bundle\FakerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('faker');

        $rootNode
            ->beforeNormalization()
                ->always(function ($v) {
                    if (isset($v['orm'])) {
                        $v['orm'] = strtolower($v['orm']);
                    }

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('seed')->defaultValue(rand())->end()
                ->scalarNode('orm')
                    ->defaultValue('propel')
                    ->validate()
                        ->ifNotInArray(array('doctrine', 'propel', 'mandango'))->thenInvalid('"orm" must be one of ("doctrine", "propel", "mandango")')
                    ->end()
                ->end()
                ->scalarNode('populator')->defaultNull()->end()
                ->scalarNode('entity')->defaultNull()->end()
                ->scalarNode('locale')->defaultValue(\Faker\Factory::DEFAULT_LOCALE)->end()
                ->arrayNode('entities')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('class')->end()
                        ->scalarNode('number')->end()
                        ->booleanNode('generate_id')
                            ->defaultFalse()->end()
                        ->arrayNode('custom_formatters')
                            ->useAttributeAsKey('column')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('method')->end()
                                ->arrayNode('parameters')
                                    ->prototype('variable')->end()
                                ->end()
                                ->booleanNode('unique')->defaultFalse()->end()
                                ->scalarNode('optional')->defaultNull()->end()
                            ->end()
                        ->end()->end()
                        ->arrayNode('custom_modifiers')
                            ->useAttributeAsKey('method')
                            ->prototype('variable')
                        ->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
