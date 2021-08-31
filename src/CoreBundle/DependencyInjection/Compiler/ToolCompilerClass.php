<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ToolCompilerClass.
 * Search the services with tag "chamilo_core.tool" in order to be added
 * as a tool (Documents, Notebook, etc).
 *
 * See:
 * https://symfony.com/doc/current/service_container/tags.html
 */
class ToolCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /*if ($container->has(ToolChain::class)) {
            $definition = $container->findDefinition(ToolChain::class);
            $taggedServices = $container->findTaggedServiceIds('chamilo_core.tool');
            foreach (array_keys($taggedServices) as $id) {
                $definition->addMethodCall('addTool', [new Reference($id)]);
            }
        }*/

        /*$services = $container->findTaggedServiceIds('doctrine.repository_service');
        foreach ($services as $service => $attributes) {
            error_log($container->getDefinition($service)->getClass());
        }*/
    }
}
