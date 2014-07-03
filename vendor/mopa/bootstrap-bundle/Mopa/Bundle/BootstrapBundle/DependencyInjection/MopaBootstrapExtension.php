<?php

/*
 * This file is part of the MopaBootstrapBundle.
 *
 * (c) Philipp A. Mohrenweiser <phiamo@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mopa\Bundle\BootstrapBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class MopaBootstrapExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        // load twig extensions
        $loader->load('twig.xml');

        $loader->load('bootstrap.xml');

        if (isset($config['bootstrap'])) {
            if (isset($config['bootstrap']['install_path'])) {
                $container->setParameter(
                    'mopa_bootstrap.bootstrap.install_path',
                    $config['bootstrap']['install_path']
                );
            } else {
                throw new \RuntimeException("Please specify install_path if specifiying bootstrap key");
            }
        }
        if (isset($config['form'])) {
            $loader->load('form.xml');
            foreach ($config['form'] as $key => $value) {
                if (is_array($value)) {
                    $this->remapParameters($container, 'mopa_bootstrap.form.'.$key, $config['form'][$key]);
                } else {
                    $container->setParameter(
                        'mopa_bootstrap.form.'.$key,
                        $value
                    );
                }
            }
        }
        // TODO: remove this
        if ($this->isConfigEnabled($container, $config['navbar'])) {
            trigger_error(sprintf('mopa_boostrap.navbar is deprecated. Use mopa_bootstrap.menu.'), E_USER_DEPRECATED);
            $loader->load('menu.xml');
            $this->remapParameters($container, 'mopa_bootstrap.menu', $config['navbar']);
        }
        /**
         * Menu
         */
        if ($this->isConfigEnabled($container, $config['menu'])) {
            $loader->load('menu.xml');
            $this->remapParameters($container, 'mopa_bootstrap.menu', $config['menu']);
        }
        /**
         * Icons
         */
        if (isset($config['icons'])) {
            $this->remapParameters($container, 'mopa_bootstrap.icons', $config['icons']);
        }

        /**
         * Initializr
         */
        if (isset($config['initializr'])) {
            $loader->load('initializr.xml');
            $this->remapParameters($container, 'mopa_bootstrap.initializr', $config['initializr']);
        }
    }

    /**
     * Remap parameters
     *
     * @param ContainerBuilder $container
     * @param string           $prefix
     * @param array            $config
     */
    private function remapParameters(ContainerBuilder $container, $prefix, array $config)
    {
        foreach ($config as $key => $value) {
            $container->setParameter(sprintf('%s.%s', $prefix, $key), $value);
        }
    }
}
