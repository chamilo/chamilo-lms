<?php

namespace ChamiloLMS\CourseBundle\DependencyInjection\Compiler;;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ToolCompilerClass
 * @package ChamiloLMS\CourseBundle\DependencyInjection\Compiler
 */
class ToolCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('chamilolms.tool_chain')) {
            return;
        }

        $definition = $container->getDefinition(
            'chamilolms.tool_chain'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'chamilolm.course.tool'
        );

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTool', array(new Reference($id)));
        }
    }
}
