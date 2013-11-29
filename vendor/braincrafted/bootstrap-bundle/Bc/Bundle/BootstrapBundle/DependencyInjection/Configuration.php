<?php
/**
 * This file is part of BcBootstrapBundle.
 *
 * (c) 2012-2013 by Florian Eckerstorfer
 */

namespace Bc\Bundle\BootstrapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration
 *
 * @package    BcBootstrapBundle
 * @subpackage DependencyInjection
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2013 Florian Eckerstorfer
 * @license    http://opensource.org/licenses/MIT The MIT License
 * @link       http://bootstrap.braincrafted.com Bootstrap for Symfony2
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        return $this->buildConfigTree();
    }

    private function buildConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('bc_bootstrap');

        $rootNode
            ->children()
            ->scalarNode('output_dir')->defaultValue('')->end()
            ->scalarNode('assets_dir')->defaultValue('%kernel.root_dir%/../vendor/twbs/bootstrap')->end()
            ->scalarNode('jquery_path')->defaultValue('%kernel.root_dir%/../vendor/jquery/jquery/jquery-1.9.1.js')->end()
            ->scalarNode('less_filter')
                ->defaultValue('less')
                ->validate()
                ->ifNotInArray(array('less', 'lessphp'))
                    ->thenInvalid('Invalid less filter "%s"')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
