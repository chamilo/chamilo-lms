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

namespace Sonata\Doctrine\Bridge\Symfony\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class AdapterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('sonata.doctrine.model.adapter.chain')) {
            return;
        }

        $definition = $container->findDefinition('sonata.doctrine.model.adapter.chain');

        if ($this->isDoctrineOrmLoaded($container)) {
            $definition->addMethodCall('addAdapter', [new Reference('sonata.doctrine.adapter.doctrine_orm')]);
        } else {
            $container->removeDefinition('sonata.doctrine.adapter.doctrine_orm');
        }

        if ($container->has('doctrine_phpcr')) {
            $definition->addMethodCall('addAdapter', [new Reference('sonata.doctrine.adapter.doctrine_phpcr')]);
        } else {
            $container->removeDefinition('sonata.doctrine.adapter.doctrine_phpcr');
        }
    }

    private function isDoctrineOrmLoaded(ContainerBuilder $container): bool
    {
        return $container->has('doctrine') && $container->has('sonata.doctrine.adapter.doctrine_orm');
    }
}
