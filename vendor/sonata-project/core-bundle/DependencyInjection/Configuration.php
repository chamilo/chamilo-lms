<?php

/*
 * This file is part of the Sonata project.
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
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 * @author Alexander <iam.asm89@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata_core');

        $this->addFlashMessageSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Returns configuration for flash messages
     *
     * @param ArrayNodeDefinition $node
     *
     * @return void
     */
    private function addFlashMessageSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
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
}
