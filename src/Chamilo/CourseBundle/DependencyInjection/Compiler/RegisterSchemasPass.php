<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register all the course settings schemas in the schema registry.
 * Save the configuration names in parameter for the provider.
 */
class RegisterSchemasPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('chamilo_course.settings.schema_registry')) {
            return;
        }

        $schemaRegistry = $container->getDefinition('chamilo_course.settings.schema_registry');

        foreach ($container->findTaggedServiceIds('chamilo_course.settings_schema') as $id => $attributes) {
            if (!array_key_exists('namespace', $attributes[0])) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must define the "namespace" attribute on "chamilo_course.settings_schema" tags.', $id));
            }

            $namespace = $attributes[0]['namespace'];

            $schemaRegistry->addMethodCall('registerSchema', [$namespace, new Reference($id)]);
        }
    }
}
