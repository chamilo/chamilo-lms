<?php

/*
 * This file is part of the FOSAdvancedEncoderBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\AdvancedEncoderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('fos_advanced_encoder');

        $rootNode
            ->fixXmlConfig('encoder')
            ->children()
                ->arrayNode('encoders')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->canBeUnset()
                        ->performNoDeepMerging()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function($v) { return array('algorithm' => $v); })
                        ->end()
                        ->children()
                            ->scalarNode('algorithm')->cannotBeEmpty()->end()
                            ->booleanNode('ignore_case')->defaultFalse()->end()
                            ->booleanNode('encode_as_base64')->defaultTrue()->end()
                            ->scalarNode('iterations')->defaultValue(5000)->end()
                            ->scalarNode('cost')->defaultValue(13)->end()
                            ->scalarNode('hash_algorithm')->defaultValue('sha512')->end()
                            ->scalarNode('key_length')->defaultValue(40)->end()
                            ->scalarNode('id')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
