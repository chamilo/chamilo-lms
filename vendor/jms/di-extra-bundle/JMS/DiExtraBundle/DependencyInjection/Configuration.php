<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\DiExtraBundle\DependencyInjection;

use Doctrine\Common\Version;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();

        $tb
            ->root('jms_di_extra', 'array')
                ->children()
                    ->arrayNode('cache_warmer')
                        ->addDefaultsIfNotSet()
                        ->treatTrueLike(array('enabled' => true))
                        ->treatFalseLike(array('enabled' => false))
                        ->children()
                            ->booleanNode('enabled')->defaultTrue()->end()
                            ->arrayNode('controller_file_blacklist')->prototype('scalar')->end()->end()
                        ->end()
                    ->end()
                    ->arrayNode('locations')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('all_bundles')->defaultFalse()->end()
                            ->arrayNode('bundles')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) {
                                        return preg_split('/\s*,\s*/', $v);
                                    })
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('directories')
                                ->beforeNormalization()
                                    ->ifString()
                                    ->then(function($v) {
                                        return preg_split('/\s*,\s*/', $v);
                                    })
                                ->end()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/jms_diextra')->end()
                    ->scalarNode('disable_grep')->defaultFalse()->end()
                    ->arrayNode('metadata')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('cache')->defaultValue('file')->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                    ->arrayNode('automatic_controller_injections')
                        ->info('Allows you to configure automatic injections for controllers. '
                                .'This is most useful for commonly needed services in controllers which then do not need to be annotated anymore.')
                        ->fixXmlConfig('property')
                        ->fixXmlConfig('method_call')
                        ->children()
                            ->arrayNode('properties')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('method_calls')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->beforeNormalization()
                                        ->ifString()
                                        ->then(function($v) {
                                            return preg_split('/\s*,\s*/', $v);
                                        })
                                    ->end()
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->booleanNode('doctrine_integration')
                        ->validate()
                            ->always(function($v) {
                                if ($v && !class_exists('Doctrine\ORM\EntityManager')) {
                                    throw new \Exception('Doctrine integration is only available for the Doctrine ORM at the moment.');
                                }

                                return $v;
                            })
                        ->end()
                        ->defaultValue(class_exists('Doctrine\ORM\EntityManager'))->end()
                ->end()
            ->end();

        return $tb;
    }
}
