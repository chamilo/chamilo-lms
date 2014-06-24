<?php

namespace Bazinga\Bundle\FakerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('faker.generator')) {
            return;
        }

        foreach ($container->findTaggedServiceIds('bazinga_faker.provider') as $id => $tags) {
            $container
                ->getDefinition('faker.generator')
                ->addMethodCall('addProvider', array(new Reference($id)))
            ;
        }
    }
}
