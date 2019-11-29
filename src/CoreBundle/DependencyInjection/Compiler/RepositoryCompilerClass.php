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
class RepositoryCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ToolChain::class)) {
            return;
        }

        /*$taggedServices = $container->findTaggedServiceIds('chamilo_core.resource_repository');
        foreach ($taggedServices as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setArguments([$definition->getClass()]);
        }*/
    }
}
