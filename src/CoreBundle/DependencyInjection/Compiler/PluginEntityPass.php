<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Chamilo\CoreBundle\Service\PluginEntityLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginEntityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var PluginEntityLoader $pluginEntityLoader */
        $pluginEntityLoader = $container->get(PluginEntityLoader::class);
        $entityDirs = $pluginEntityLoader->getEntityDirectories();

        $metadataDriverDefinition = $container->getDefinition('doctrine.orm.default_metadata_driver');

        foreach ($entityDirs as $dir) {
            $pluginTitle = ucwords(basename(\dirname($dir)));
            $namespace = "Chamilo\\PluginBundle\\$pluginTitle";

            $driverReference = new Reference('doctrine.orm.default_attribute_metadata_driver');

            $metadataDriverDefinition->addMethodCall('addDriver', [$driverReference, $namespace]);
        }
    }
}
