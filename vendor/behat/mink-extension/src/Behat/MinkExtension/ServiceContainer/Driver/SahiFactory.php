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

class SahiFactory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'sahi';
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
                ->scalarNode('sid')->defaultNull()->end()
                ->scalarNode('host')->defaultValue('localhost')->end()
                ->scalarNode('port')->defaultValue(9999)->end()
                ->scalarNode('browser')->defaultNull()->end()
                ->scalarNode('limit')->defaultValue(600)->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        if (!class_exists('Behat\Mink\Driver\SahiDriver')) {
            throw new \RuntimeException(
                'Install MinkSahiDriver in order to use sahi driver.'
            );
        }

        return new Definition('Behat\Mink\Driver\SahiDriver', array(
            '%mink.browser_name%',
            new Definition('Behat\SahiClient\Client', array(
                new Definition('Behat\SahiClient\Connection', array(
                    $config['sid'],
                    $config['host'],
                    $config['port'],
                    $config['browser'],
                    $config['limit'],
                )),
            )),
        ));
    }
}
