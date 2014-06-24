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

namespace JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Enhances the access_control section configuration.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AccessControlConfiguration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('security');

        $rootNode
            ->ignoreExtraKeys()
            ->fixXmlConfig('rule', 'access_control')
            ->children()
                ->arrayNode('access_control')
                    ->cannotBeOverwritten()
                    ->prototype('array')
                        ->fixXmlConfig('role')
                        ->validate()
                            ->always(function($v) {
                                if (!empty($v['roles']) && isset($v['access'])) {
                                    throw new \Exception('"roles", and "access" cannot be set at the same time.');
                                }

                                if (empty($v['roles'])) {
                                    unset($v['roles']);
                                }

                                return $v;
                            })
                        ->end()
                        ->children()
                            ->scalarNode('requires_channel')->defaultNull()->end()
                            ->scalarNode('path')->defaultNull()->end()
                            ->scalarNode('host')->defaultNull()->end()
                            ->scalarNode('ip')->defaultNull()->end()
                            ->arrayNode('methods')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('roles')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('access')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tb;
    }
}
