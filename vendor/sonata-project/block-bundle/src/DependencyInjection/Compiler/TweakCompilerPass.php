<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Link the block service to the Page Manager.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class TweakCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition('sonata.block.manager');
        $registry = $container->getDefinition('sonata.block.menu.registry');

        $parameters = $container->getParameter('sonata_block.blocks');

        foreach ($container->findTaggedServiceIds('sonata.block') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->setPublic(true);

            $arguments = $definition->getArguments();

            // Replace empty block id with service id
            if (empty($arguments) || 0 == strlen($arguments[0])) {
                // NEXT_MAJOR: Remove the condition when Symfony 2.8 support will be dropped.
                if (method_exists($definition, 'setArgument')) {
                    $definition->setArgument(0, $id);
                } else {
                    $definition->replaceArgument(0, $id);
                }
            } elseif ($id != $arguments[0] && 0 !== strpos(
                $container->getParameterBag()->resolveValue($definition->getClass()),
                'Sonata\\BlockBundle\\Block\\Service\\'
            )) {
                // NEXT_MAJOR: Remove deprecation notice
                @trigger_error(
                    sprintf('Using service id %s different from block id %s is deprecated since 3.3 and will be removed in 4.0.', $id, $arguments[0]),
                    E_USER_DEPRECATED
                );
            }

            $manager->addMethodCall('add', [$id, $id, isset($parameters[$id]) ? $parameters[$id]['contexts'] : []]);
        }

        foreach ($container->findTaggedServiceIds('knp_menu.menu') as $id => $tags) {
            foreach ($tags as $attributes) {
                if (empty($attributes['alias'])) {
                    throw new \InvalidArgumentException(sprintf('The alias is not defined in the "knp_menu.menu" tag for the service "%s"', $id));
                }
                $registry->addMethodCall('add', [$attributes['alias']]);
            }
        }

        $services = [];
        foreach ($container->findTaggedServiceIds('sonata.block.loader') as $id => $tags) {
            $services[] = new Reference($id);
        }

        $container->getDefinition('sonata.block.loader.chain')->replaceArgument(0, $services);

        $this->applyContext($container);
    }

    /**
     * Apply configurations to the context manager.
     *
     * @param ContainerBuilder $container
     */
    public function applyContext(ContainerBuilder $container)
    {
        $definition = $container->findDefinition('sonata.block.context_manager');

        foreach ($container->getParameter('sonata_block.blocks') as $service => $settings) {
            if (count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByType', [$service, $settings['settings'], true]);
            }
        }
        foreach ($container->getParameter('sonata_block.blocks_by_class') as $class => $settings) {
            if (count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByClass', [$class, $settings['settings'], true]);
            }
        }
    }
}
