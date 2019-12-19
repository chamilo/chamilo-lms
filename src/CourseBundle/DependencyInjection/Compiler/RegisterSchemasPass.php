<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register all the course settings schemas in the schema registry.
 * The services with the tag: chamilo_course.settings_schema.
 */
class RegisterSchemasPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('chamilo_course.registry.settings_schema')) {
            return;
        }
        $schemaRegistry = $container->getDefinition('chamilo_course.registry.settings_schema');
        $taggedServicesIds = $container->findTaggedServiceIds('chamilo_course.settings_schema');

        foreach ($taggedServicesIds as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['alias'])) {
                    throw new \InvalidArgumentException(sprintf('Service "%s" must define the "alias" attribute on "sylius.settings_schema" tags.', $id));
                }

                $schemaRegistry->addMethodCall('register', [$attributes['alias'], new Reference($id)]);
            }
        }
    }
}
