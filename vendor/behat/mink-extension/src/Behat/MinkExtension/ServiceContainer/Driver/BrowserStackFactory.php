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

class BrowserStackFactory extends Selenium2Factory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'browser_stack';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('username')->defaultValue(getenv('BROWSERSTACK_USERNAME'))->end()
                ->scalarNode('access_key')->defaultValue(getenv('BROWSERSTACK_ACCESS_KEY'))->end()
                ->scalarNode('browser')->defaultValue('firefox')->end()
                ->append($this->getCapabilitiesNode())
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        $config['wd_host'] = sprintf('%s:%s@hub.browserstack.com/wd/hub', $config['username'], $config['access_key']);

        return parent::buildDriver($config);
    }

    protected function getCapabilitiesNode()
    {
        $node = parent::getCapabilitiesNode();

        $node
            ->children()
                ->scalarNode('project')->end()
                ->scalarNode('resolution')->end()
                ->scalarNode('build')->info('will be set automatically based on the TRAVIS_JOB_NUMBER environment variable if available')->end()
                ->scalarNode('os')->end()
                ->scalarNode('os_version')->end()
                ->scalarNode('device')->end()
                ->booleanNode('browserstack-debug')->end()
                ->booleanNode('browserstack-tunnel')->end()
                ->booleanNode('emulator')->end()
                ->booleanNode('acceptSslCert')->end()
            ->end()
        ;

        return $node;
    }
}
