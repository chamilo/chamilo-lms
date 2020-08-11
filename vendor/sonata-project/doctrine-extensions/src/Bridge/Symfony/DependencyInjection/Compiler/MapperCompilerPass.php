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

use Sonata\Doctrine\Mapper\DoctrineCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
final class MapperCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$this->isDoctrineOrmLoaded($container)) {
            $container->removeDefinition('sonata.doctrine.mapper');

            return;
        }

        $mapper = $container->getDefinition('sonata.doctrine.mapper');
        $collector = DoctrineCollector::getInstance();

        foreach ($collector->getAssociations() as $class => $associations) {
            foreach ($associations as $type => $options) {
                $mapper->addMethodCall('addAssociation', [$class, $type, $options]);
            }
        }

        foreach ($collector->getDiscriminatorColumns() as $class => $columnDefinition) {
            $mapper->addMethodCall('addDiscriminatorColumn', [$class, $columnDefinition]);
        }

        foreach ($collector->getDiscriminators() as $class => $discriminators) {
            foreach ($discriminators as $key => $discriminatorClass) {
                $mapper->addMethodCall('addDiscriminator', [$class, $key, $discriminatorClass]);
            }
        }

        foreach ($collector->getInheritanceTypes() as $class => $type) {
            $mapper->addMethodCall('addInheritanceType', [$class, $type]);
        }

        foreach ($collector->getIndexes() as $class => $indexes) {
            foreach ($indexes as $field => $options) {
                $mapper->addMethodCall('addIndex', [$class, $field, $options]);
            }
        }

        foreach ($collector->getUniques() as $class => $uniques) {
            foreach ($uniques as $field => $options) {
                $mapper->addMethodCall('addUnique', [$class, $field, $options]);
            }
        }

        foreach ($collector->getOverrides() as $class => $overrides) {
            foreach ($overrides as $type => $options) {
                $mapper->addMethodCall('addOverride', [$class, $type, $options]);
            }
        }

        $collector->clear();
    }

    private function isDoctrineOrmLoaded(ContainerBuilder $container): bool
    {
        return $container->hasDefinition('doctrine') && $container->hasDefinition('sonata.doctrine.mapper');
    }
}
