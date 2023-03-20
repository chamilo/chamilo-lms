<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ChamiloCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('settings.yml');
        $loader->load('repositories.yml');
        $loader->load('tool_settings.yml');
        $loader->load('listeners.yml');

        $loader = new Loader\XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'chamilo_core';
    }
}
