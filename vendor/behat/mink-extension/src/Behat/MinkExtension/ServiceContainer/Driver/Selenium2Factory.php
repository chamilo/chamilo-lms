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

class Selenium2Factory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'selenium2';
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
                ->scalarNode('browser')->defaultValue('%mink.browser_name%')->end()
                ->append($this->getCapabilitiesNode())
                ->scalarNode('wd_host')->defaultValue('http://localhost:4444/wd/hub')->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        if (!class_exists('Behat\Mink\Driver\Selenium2Driver')) {
            throw new \RuntimeException(sprintf(
                'Install MinkSelenium2Driver in order to use %s driver.',
                $this->getDriverName()
            ));
        }

        $extraCapabilities = $config['capabilities']['extra_capabilities'];
        unset($config['capabilities']['extra_capabilities']);

        if (getenv('TRAVIS_JOB_NUMBER')) {
            $guessedCapabilities = array(
                'tunnel-identifier' => getenv('TRAVIS_JOB_NUMBER'),
                'build' => getenv('TRAVIS_BUILD_NUMBER'),
                'tags' => array('Travis-CI', 'PHP '.phpversion()),
            );
        } elseif (getenv('JENKINS_HOME')) {
            $guessedCapabilities = array(
                'tunnel-identifier' => getenv('JOB_NAME'),
                'build' => getenv('BUILD_NUMBER'),
                'tags' => array('Jenkins', 'PHP '.phpversion(), getenv('BUILD_TAG')),
            );
        } else {
            $guessedCapabilities = array(
                'tags' => array(php_uname('n'), 'PHP '.phpversion()),
            );
        }

        return new Definition('Behat\Mink\Driver\Selenium2Driver', array(
            $config['browser'],
            array_replace($guessedCapabilities, $extraCapabilities, $config['capabilities']),
            $config['wd_host'],
        ));
    }

    protected function getCapabilitiesNode()
    {
        $node = new ArrayNodeDefinition('capabilities');

        $node
            ->addDefaultsIfNotSet()
            ->normalizeKeys(false)
            ->children()
                ->scalarNode('browserName')->end()
                ->scalarNode('version')->end()
                ->scalarNode('platform')->end()
                ->scalarNode('browserVersion')->end()
                ->scalarNode('browser')->defaultValue('firefox')->end()
                ->booleanNode('marionette')->defaultNull()->end()
                ->booleanNode('ignoreZoomSetting')->defaultFalse()->end()
                ->scalarNode('name')->defaultValue('Behat feature suite')->end()
                ->scalarNode('deviceOrientation')->end()
                ->scalarNode('deviceType')->end()
                ->booleanNode('javascriptEnabled')->end()
                ->booleanNode('databaseEnabled')->end()
                ->booleanNode('locationContextEnabled')->end()
                ->booleanNode('applicationCacheEnabled')->end()
                ->booleanNode('browserConnectionEnabled')->end()
                ->booleanNode('webStorageEnabled')->end()
                ->booleanNode('rotatable')->end()
                ->booleanNode('acceptSslCerts')->end()
                ->booleanNode('nativeEvents')->end()
                ->booleanNode('overlappingCheckDisabled')->end()
                ->arrayNode('proxy')
                    ->children()
                        ->scalarNode('proxyType')->end()
                        ->scalarNode('proxyAuthconfigUrl')->end()
                        ->scalarNode('ftpProxy')->end()
                        ->scalarNode('httpProxy')->end()
                        ->scalarNode('sslProxy')->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return empty($v);
                        })
                        ->thenUnset()
                    ->end()
                ->end()
                ->arrayNode('firefox')
                    ->children()
                        ->scalarNode('profile')
                            ->validate()
                                ->ifTrue(function ($v) {
                                    return !file_exists($v);
                                })
                                ->thenInvalid('Cannot find profile zip file %s')
                            ->end()
                        ->end()
                        ->scalarNode('binary')->end()
                    ->end()
                ->end()
                ->arrayNode('chrome')
                    ->children()
                        ->arrayNode('switches')->prototype('scalar')->end()->end()
                        ->scalarNode('binary')->end()
                        ->arrayNode('extensions')->prototype('scalar')->end()->end()
                    ->end()
                ->end()
                ->arrayNode('extra_capabilities')
                    ->info('Custom capabilities merged with the known ones')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                ->end()
            ->end();

        return $node;
    }
}
