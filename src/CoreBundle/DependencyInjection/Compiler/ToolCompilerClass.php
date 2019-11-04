<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ToolCompilerClass.
 * Search the services with tag "chamilo_core.tool" in order to be added
 * as a tool (Documents, Notebook, etc).
 */
class ToolCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('chamilo_core.tool_chain')) {
            return;
        }

        $definition = $container->getDefinition('chamilo_core.tool_chain');
        $taggedServices = $container->findTaggedServiceIds('chamilo_core.tool');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTool', [new Reference($id)]);
        }
    }
}
