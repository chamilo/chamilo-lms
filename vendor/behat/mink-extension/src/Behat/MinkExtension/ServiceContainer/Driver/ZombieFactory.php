<?php

/*
 * This file is part of the Behat MinkExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\MinkExtension\ServiceContainer\Driver;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

class ZombieFactory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'zombie';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsJavascript()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                ->scalarNode('port')->defaultValue(8124)->end()
                ->scalarNode('node_bin')->defaultValue('node')->end()
                ->scalarNode('server_path')->defaultNull()->end()
                ->scalarNode('threshold')->defaultValue(2000000)->end()
                ->scalarNode('node_modules_path')->defaultValue('')->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        if (!class_exists('Behat\Mink\Driver\ZombieDriver')) {
            throw new \RuntimeException(
                'Install MinkZombieDriver in order to use zombie driver.'
            );
        }

        return new Definition('Behat\Mink\Driver\ZombieDriver', array(
            new Definition('Behat\Mink\Driver\NodeJS\Server\ZombieServer', array(
                $config['host'],
                $config['port'],
                $config['node_bin'],
                $config['server_path'],
                $config['threshold'],
                $config['node_modules_path'],
            )),
        ));
    }
}
