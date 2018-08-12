<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Bridge\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class ExporterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('sonata.exporter.exporter')) {
            return;
        }

        $definition = $container->findDefinition('sonata.exporter.exporter');
        $writers = $container->findTaggedServiceIds('sonata.exporter.writer');

        foreach (array_keys($writers) as $id) {
            $definition->addMethodCall('addWriter', [new Reference($id)]);
        }
    }
}
