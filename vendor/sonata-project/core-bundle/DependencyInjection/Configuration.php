<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 * @author Alexander <iam.asm89@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata_core');

        $this->addFlashMessageSection($rootNode);
        $this->addSerializerFormats($rootNode);

        $rootNode
            ->children()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mapping')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->defaultValue(true)
                                ->end()
                                ->arrayNode('type')
                                    ->useAttributeAsKey('id')
                                    ->defaultValue(array())
                                    ->prototype('scalar')->end()
                                ->end()

                                ->arrayNode('extension')
                                    ->useAttributeAsKey('id')
                                    ->defaultValue(array())
                                    ->prototype('array')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * Returns configuration for flash messages.
     *
     * @param ArrayNodeDefinition $node
     */
    private function addFlashMessageSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('form_type')
                    ->defaultValue('standard')
                    ->validate()
                    ->ifNotInArray($validFormTypes = array('standard', 'horizontal'))
                        ->thenInvalid(sprintf(
                            'The form_type option value must be one of %s',
                            $validFormTypesString = implode(', ', $validFormTypes)
                        ))
                    ->end()
                    ->info(sprintf('Must be one of %s', $validFormTypesString))
                ->end()
                ->arrayNode('flashmessage')
                    ->useAttributeAsKey('message')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('css_class')->end()
                            ->arrayNode('types')
                                ->useAttributeAsKey('type')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('domain')->defaultValue('SonataCoreBundle')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Returns configuration for serializer formats.
     *
     * @param ArrayNodeDefinition $node
     */
    private function addSerializerFormats(ArrayNodeDefinition $node)
    {
        // NEXT_MAJOR : do not execute this if jms/serializer is missing
        $node
            ->children()
                ->arrayNode('serializer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('formats')
                            ->prototype('scalar')->end()
                            ->defaultValue(array('json', 'xml', 'yml'))
                            ->info('Default serializer formats, will be used while getting subscribing methods.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
