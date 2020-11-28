<?php

namespace XApi\LrsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder
            ->root('xapi_lrs')
                ->beforeNormalization()
                    ->ifTrue(function ($v) { return isset($v['type']) && in_array($v['type'], ['mongodb', 'orm']) && !isset($v['object_manager_service']); })
                    ->thenInvalid('You need to configure the object manager service when the repository type is "mongodb" or orm".')
                ->end()
                ->children()
                    ->enumNode('type')
                        ->isRequired()
                        ->values(['in_memory', 'mongodb', 'orm'])
                    ->end()
                    ->scalarNode('object_manager_service')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
