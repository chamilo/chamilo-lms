<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Chamilo\CoreBundle\Helpers\PluginEntityLoaderHelper;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginEntityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var PluginEntityLoaderHelper $pluginEntityLoaderHelper */
        $pluginEntityLoaderHelper = $container->get(PluginEntityLoaderHelper::class);
        $entityDirs = $pluginEntityLoaderHelper->getEntityDirectories();

        $metadataDriverDefinition = $container->getDefinition('doctrine.orm.default_metadata_driver');

        foreach ($entityDirs as $dir) {
            $pluginTitle = basename(\dirname($dir));

            if ('src' === $pluginTitle) {
                $pluginTitle = basename(\dirname($dir, 2));
            }

            $namespace = "Chamilo\\PluginBundle\\$pluginTitle";

            $driverReference = new Reference('doctrine.orm.default_attribute_metadata_driver');

            $metadataDriverDefinition->addMethodCall('addDriver', [$driverReference, $namespace]);
        }
    }
}
