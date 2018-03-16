<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ToolCompilerClass
 * Loads the services with tag "chamilo_course.tool" in order to be added
 * as a course tool (Documents, Notebook, etc).
 *
 * @package Chamilo\CourseBundle\DependencyInjection\Compiler
 */
class ToolCompilerClass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('chamilo_course.tool_chain')) {
            return;
        }

        $definition = $container->getDefinition(
            'chamilo_course.tool_chain'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'chamilo_course.tool'
        );

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTool', [new Reference($id)]);
        }
    }
}
