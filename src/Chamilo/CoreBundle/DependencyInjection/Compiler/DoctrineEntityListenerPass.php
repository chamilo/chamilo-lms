<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class DoctrineEntityListenerPass.
 *
 * @package Chamilo\CoreBundle\DependencyInjection\Compiler
 */
class DoctrineEntityListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('chamilo.doctrine.entity_listener_resolver');
        $services = $container->findTaggedServiceIds('doctrine.entity_listener');

        foreach ($services as $service => $attributes) {
            $definition->addMethodCall(
                'addMapping',
                [$container->getDefinition($service)->getClass(), $service]
            );
        }
    }
}
