<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Chamilo\CoreBundle\ToolChain;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ToolChain::class)) {
            return;
        }

        $definition = $container->findDefinition(ToolChain::class);
        $taggedServices = $container->findTaggedServiceIds('chamilo_core.tool');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTool', [new Reference($id)]);
        }

        /*$services = $container->findTaggedServiceIds('doctrine.repository_service');
        foreach ($services as $service => $attributes) {
            error_log($container->getDefinition($service)->getClass());
        }*/
    }
}
