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

class SeleniumFactory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'selenium';
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
                ->scalarNode('port')->defaultValue(4444)->end()
                ->scalarNode('browser')->defaultValue('*%mink.browser_name%')->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        if (!class_exists('Behat\Mink\Driver\SeleniumDriver')) {
            throw new \RuntimeException(
                'Install MinkSeleniumDriver in order to activate selenium session.'
            );
        }

        return new Definition('Behat\Mink\Driver\SeleniumDriver', array(
            $config['browser'],
            '%mink.base_url%',
            new Definition('Selenium\Client', array(
                $config['host'],
                $config['port'],
            )),
        ));
    }
}
