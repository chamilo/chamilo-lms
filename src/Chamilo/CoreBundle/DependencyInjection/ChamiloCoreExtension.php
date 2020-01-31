<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class ChamiloCoreExtension.
 *
 * @package Chamilo\CoreBundle\DependencyInjection
 */
class ChamiloCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('services.yml');
        $loader->load('admin.yml');
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'chamilo_core';
    }
}
