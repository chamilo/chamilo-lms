<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('chamilo_course');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('driver')->defaultValue('doctrine/orm')->cannotBeEmpty()->end()
            ->end()
        ;

        $this->addClassesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `classes` section.
     */
    private function addClassesSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
            ->arrayNode('classes')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('parameter')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('model')->defaultValue('Sylius\Bundle\SettingsBundle\Model\Parameter')->cannotBeEmpty()->end()
            ->scalarNode('controller')->defaultValue('Sylius\Bundle\ResourceBundle\Controller\ResourceController')->end()
            ->scalarNode('repository')->cannotBeEmpty()->end()
            ->scalarNode('form')->defaultValue(
                'MyApp\MyCustomBundle\Form\Type\MyformType'
            )->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }
}
