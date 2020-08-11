<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\DependencyInjection\Compiler;

use Sonata\EasyExtendsBundle\Mapper\DoctrineCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class AddMapperInformationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('doctrine')) {
            $container->removeDefinition('sonata.easy_extends.doctrine.mapper');

            return;
        }

        $mapper = $container->getDefinition('sonata.easy_extends.doctrine.mapper');

        foreach (DoctrineCollector::getInstance()->getAssociations() as $class => $associations) {
            foreach ($associations as $field => $options) {
                $mapper->addMethodCall('addAssociation', [$class, $field, $options]);
            }
        }

        foreach (DoctrineCollector::getInstance()->getDiscriminatorColumns() as $class => $columnDefinition) {
            $mapper->addMethodCall('addDiscriminatorColumn', [$class, $columnDefinition]);
        }

        foreach (DoctrineCollector::getInstance()->getDiscriminators() as $class => $discriminators) {
            foreach ($discriminators as $key => $discriminatorClass) {
                $mapper->addMethodCall('addDiscriminator', [$class, $key, $discriminatorClass]);
            }
        }

        foreach (DoctrineCollector::getInstance()->getInheritanceTypes() as $class => $type) {
            $mapper->addMethodCall('addInheritanceType', [$class, $type]);
        }

        foreach (DoctrineCollector::getInstance()->getIndexes() as $class => $indexes) {
            foreach ($indexes as $field => $options) {
                $mapper->addMethodCall('addIndex', [$class, $field, $options]);
            }
        }

        foreach (DoctrineCollector::getInstance()->getUniques() as $class => $uniques) {
            foreach ($uniques as $field => $options) {
                $mapper->addMethodCall('addUnique', [$class, $field, $options]);
            }
        }

        foreach (DoctrineCollector::getInstance()->getOverrides() as $class => $overrides) {
            foreach ($overrides as $type => $options) {
                $mapper->addMethodCall('addOverride', [$class, $type, $options]);
            }
        }

        DoctrineCollector::getInstance()->clear();
    }
}
