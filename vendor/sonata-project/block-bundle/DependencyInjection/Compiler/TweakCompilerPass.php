<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Link the block service to the Page Manager
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class TweakCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition('sonata.block.manager');

        $parameters = $container->getParameter('sonata_block.blocks');

        foreach ($container->findTaggedServiceIds('sonata.block') as $id => $attributes) {
            $manager->addMethodCall('add', array($id, $id, isset($parameters[$id]) ? $parameters[$id]['contexts'] : array()));
        }

        $services = array();
        foreach ($container->findTaggedServiceIds('sonata.block.loader') as $id => $attributes) {
            $services[] = new Reference($id);
        }

        $container->getDefinition('sonata.block.loader.chain')->replaceArgument(0, $services);

        $this->applyContext($container);
    }

    /**
     * Apply configurations to the context manager
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
