<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\DependencyInjection\Compiler;

use Sonata\BlockBundle\Naming\ConvertFromFqcn;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Link the block service to the Page Manager.
 *
 * @final since sonata-project/block-bundle 3.0
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class TweakCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $manager = $container->getDefinition('sonata.block.manager');
        $registry = $container->getDefinition('sonata.block.menu.registry');

        $blocks = $container->getParameter('sonata_block.blocks');
        $blockTypes = $container->getParameter('sonata_blocks.block_types');
        $cacheBlocks = $container->getParameter('sonata_block.cache_blocks');
        $defaultContexs = $container->getParameter('sonata_blocks.default_contexts');

        foreach ($container->findTaggedServiceIds('sonata.block') as $id => $tags) {
            $definition = $container->getDefinition($id);
            $definition->setPublic(true);

            if (!$definition->isAutowired()) {
                $this->replaceBlockName($container, $definition, $id);
            }

            $blockId = $this->getBlockId($id);
            $settings = $this->createBlockSettings($id, $tags, $defaultContexs);

            // Register blocks dynamicaly
            if (!\array_key_exists($blockId, $blocks)) {
                $blocks[$blockId] = $settings;
            }
            if (!\in_array($blockId, $blockTypes, true)) {
                $blockTypes[] = $blockId;
            }
            if (isset($cacheBlocks['by_type']) && !\array_key_exists($blockId, $cacheBlocks['by_type'])) {
                $cacheBlocks['by_type'][$blockId] = $settings['cache'];
            }

            $manager->addMethodCall('add', [$id, $id, $settings['contexts']]);
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

        $container->setParameter('sonata_block.blocks', $blocks);
        $container->setParameter('sonata_blocks.block_types', $blockTypes);
        $container->setParameter('sonata_block.cache_blocks', $cacheBlocks);

        $container->getDefinition('sonata.block.loader.service')->replaceArgument(0, $blockTypes);
        $container->getDefinition('sonata.block.loader.chain')->replaceArgument(0, $services);

        $this->applyContext($container);
    }

    /**
     * Apply configurations to the context manager.
     */
    public function applyContext(ContainerBuilder $container)
    {
        $definition = $container->findDefinition('sonata.block.context_manager');

        foreach ($container->getParameter('sonata_block.blocks') as $service => $settings) {
            if (\count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByType', [$service, $settings['settings'], true]);
            }
        }
        foreach ($container->getParameter('sonata_block.blocks_by_class') as $class => $settings) {
            if (\count($settings['settings']) > 0) {
                $definition->addMethodCall('addSettingsByClass', [$class, $settings['settings'], true]);
            }
        }
    }

    private function getBlockId(string $id): string
    {
        $blockId = $id;

        // Only convert class service names
        if (false !== strpos($blockId, '\\')) {
            $convert = (new ConvertFromFqcn());
            $blockId = $convert($blockId);
        }

        return $blockId;
    }

    private function createBlockSettings(string $id, array $tags = [], array $defaultContexts = []): array
    {
        $contexts = $this->getContextFromTags($tags);

        if (0 === \count($contexts)) {
            $contexts = $defaultContexts;
        }

        return [
            'contexts' => $contexts,
            'templates' => [],
            'cache' => 'sonata.cache.noop',
            'settings' => [],
        ];
    }

    /**
     * Replaces the empty service name with the service id.
     */
    private function replaceBlockName(ContainerBuilder $container, Definition $definition, $id)
    {
        $arguments = $definition->getArguments();

        // Replace empty block id with service id
        if ($this->serviceDefinitionNeedsFirstArgument($definition)) {
            // NEXT_MAJOR: Remove the if block when Symfony 2.8 support will be dropped.
            if (method_exists($definition, 'setArgument')) {
                $definition->setArgument(0, $id);

                return;
            }

            $definition->replaceArgument(0, $id);
        }
    }

    private function serviceDefinitionNeedsFirstArgument(Definition $definition): bool
    {
        $arguments = $definition->getArguments();

        return empty($arguments) ||
            null === ($arguments[0]) ||
            \is_string($arguments[0]) && 0 === \strlen($arguments[0]);
    }

    /**
     * @param string[][]
     *
     * @return string[]
     */
    private function getContextFromTags(array $tags)
    {
        return array_filter(array_map(static function (array $attribute) {
            if (\array_key_exists('context', $attribute) && \is_string($attribute['context'])) {
                return $attribute['context'];
            }

            return null;
        }, $tags));
    }
}
