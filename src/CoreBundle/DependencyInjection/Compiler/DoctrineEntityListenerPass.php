<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineEntityListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('chamilo.doctrine.entity_listener_resolver');
        $services = $container->findTaggedServiceIds('doctrine.entity_listener');

        foreach (array_keys($services) as $service) {
            $definition->addMethodCall(
                'addMapping',
                [$container->getDefinition($service)->getClass(), $service]
            );
        }
    }
}
