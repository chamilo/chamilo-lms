<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\LrsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class XApiLrsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('controller.xml');
        $loader->load('event_listener.xml');
        $loader->load('factory.xml');
        $loader->load('serializer.xml');

        switch ($config['type']) {
            case 'in_memory':
                break;
            case 'mongodb':
                $loader->load('doctrine.xml');
                $loader->load('mongodb.xml');

                $container->setAlias('xapi_lrs.doctrine.object_manager', $config['object_manager_service']);
                $container->setAlias('xapi_lrs.repository.statement', 'xapi_lrs.repository.statement.doctrine');
                break;
            case 'orm':
                $loader->load('doctrine.xml');
                $loader->load('orm.xml');

                $container->setAlias('xapi_lrs.doctrine.object_manager', $config['object_manager_service']);
                $container->setAlias('xapi_lrs.repository.statement', 'xapi_lrs.repository.statement.doctrine');
                break;
        }
    }

    public function getAlias()
    {
        return 'xapi_lrs';
    }
}
