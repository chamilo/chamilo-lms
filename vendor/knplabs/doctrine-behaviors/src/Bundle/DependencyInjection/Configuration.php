<?php

namespace Knp\DoctrineBehaviors\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder
            ->root('knp_doctrine_behaviors')
            ->beforeNormalization()
                ->always(function (array $config) {
                    if (empty($config)) {
                        return [
                            'blameable'      => true,
                            'geocodable'     => true,
                            'loggable'       => true,
                            'sluggable'      => true,
                            'soft_deletable' => true,
                            'sortable'       => true,
                            'timestampable'  => true,
                            'translatable'   => true,
                            'tree'           => true,
                        ];
                    }

                    return $config;
                })
            ->end()
            ->children()
                ->booleanNode('blameable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('geocodable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('loggable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('sluggable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('soft_deletable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('sortable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('timestampable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('translatable')->defaultFalse()->treatNullLike(false)->end()
                ->booleanNode('tree')->defaultFalse()->treatNullLike(false)->end()
            ->end()
        ;

        return $builder;
    }
}
