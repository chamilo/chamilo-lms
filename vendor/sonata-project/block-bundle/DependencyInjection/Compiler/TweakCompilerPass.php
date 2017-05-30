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

            $arguments = $definition->getArguments();

            // Replace empty block id with service id
            if (strlen($arguments[0]) == 0) {
                $definition->replaceArgument(0, $id);
            } elseif ($id != $arguments[0] && 0 !== strpos(
                $container->getParameterBag()->resolveValue($definition->getClass()),
                'Sonata\\BlockBundle\\Block\\Service\\'
            )) {
                // NEXT_MAJOR: Remove deprecation notice
                @trigger_error(
                    sprintf('Using service id %s different from block id %s is deprecated since 3.x and will be removed in 4.0.', $id, $arguments[0]),
                    E_USER_DEPRECATED
                );
            }

            $manager->addMethodCall('add', array($id, $id, isset($parameters[$id]) ? $parameters[$id]['contexts'] : array()));
        }

        foreach ($container->findTaggedServiceIds('sonata.block.menu') as $id => $attributes) {
            $registry->addMethodCall('add', array(new Reference($id)));
        }

        $services = array();
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
                $definition->addMethodCall('addSettingsByType', array($service, $settings['settings'], true));
            }
        }
        foreach ($container->getParameter('sonata_block.blocks_by_class') as $class => $settings) {
            if (count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByClass', array($class, $settings['settings'], true));
            }
        }
    }
}
