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

namespace Sonata\Doctrine\Mapper\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;

final class DoctrineORMMapper implements EventSubscriber
{
    /**
     * @var array
     */
    private $associations = [];

    /**
     * @var array
     */
    private $discriminators = [];

    /**
     * @var array
     */
    private $discriminatorColumns = [];

    /**
     * @var array
     */
    private $inheritanceTypes = [];

    /**
     * @var array
     */
    private $indexes = [];

    /**
     * @var array
     */
    private $uniques = [];

    /**
     * @var array
     */
    private $overrides = [];

    public function getSubscribedEvents(): array
    {
        return [
            'loadClassMetadata',
        ];
    }

    /**
     * @param array $options
     */
    public function addAssociation(string $class, string $type, $options): void
    {
        // NEXT_MAJOR: Move array check to method signature
        if (!\is_array($options)) {
            @trigger_error(sprintf(
                'Passing other type than array as argument 3 for method %s() is deprecated since sonata-project/doctrine-extensions 1.8. It will accept only array in version 2.0.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        if (!isset($this->associations[$class])) {
            $this->associations[$class] = [];
        }

        $this->associations[$class][$type] = $options;
    }

    /**
     * Add a discriminator to a class.
     *
     * @param string $key                Key is the database value and values are the classes
     * @param string $discriminatorClass The mapped class
     */
    public function addDiscriminator(string $class, string $key, string $discriminatorClass): void
    {
        if (!isset($this->discriminators[$class])) {
            $this->discriminators[$class] = [];
        }

        if (!isset($this->discriminators[$class][$key])) {
            $this->discriminators[$class][$key] = $discriminatorClass;
        }
    }

    /**
     * @param array $columnDef
     */
    public function addDiscriminatorColumn(string $class, $columnDef): void
    {
        // NEXT_MAJOR: Move array check to method signature
        if (!\is_array($columnDef)) {
            @trigger_error(sprintf(
                'Passing other type than array as argument 2 for method %s() is deprecated since sonata-project/doctrine-extensions 1.8. It will accept only array in version 2.0.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        if (!isset($this->discriminatorColumns[$class])) {
            $this->discriminatorColumns[$class] = $columnDef;
        }
    }

    /**
     * @see ClassMetadata for supported types
     */
    public function addInheritanceType(string $class, int $type): void
    {
        if (!isset($this->inheritanceTypes[$class])) {
            $this->inheritanceTypes[$class] = $type;
        }
    }

    /**
     * @param array<string> $columns
     */
    public function addIndex(string $class, string $name, array $columns): void
    {
        $this->verifyColumnNames($columns);

        if (!isset($this->indexes[$class])) {
            $this->indexes[$class] = [];
        }

        if (isset($this->indexes[$class][$name])) {
            return;
        }

        $this->indexes[$class][$name] = $columns;
    }

    /**
     * @param array<string> $columns
     */
    public function addUnique(string $class, string $name, array $columns): void
    {
        $this->verifyColumnNames($columns);

        if (!isset($this->uniques[$class])) {
            $this->uniques[$class] = [];
        }

        if (isset($this->uniques[$class][$name])) {
            return;
        }

        $this->uniques[$class][$name] = $columns;
    }

    /**
     * @param array $options
     */
    public function addOverride(string $class, string $type, $options): void
    {
        // NEXT_MAJOR: Move array check to method signature
        if (!\is_array($options)) {
            @trigger_error(sprintf(
                'Passing other type than array as argument 3 for method %s() is deprecated since sonata-project/doctrine-extensions 1.8. It will accept only array in version 2.0.',
                __METHOD__
            ), E_USER_DEPRECATED);
        }

        if (!isset($this->overrides[$class])) {
            $this->overrides[$class] = [];
        }

        $this->overrides[$class][$type] = $options;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        $metadata = $eventArgs->getClassMetadata();

        $this->loadAssociations($metadata);
        $this->loadIndexes($metadata);
        $this->loadUniques($metadata);

        $this->loadDiscriminatorColumns($metadata);
        $this->loadDiscriminators($metadata);
        $this->loadInheritanceTypes($metadata);
        $this->loadOverrides($metadata);
    }

    /**
     * @throws \RuntimeException
     */
    private function loadAssociations(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->associations)) {
            return;
        }

        try {
            foreach ($this->associations[$metadata->getName()] as $type => $mappings) {
                foreach ($mappings as $mapping) {
                    // the association is already set, skip the native one
                    if ($metadata->hasAssociation($mapping['fieldName'])) {
                        continue;
                    }

                    \call_user_func([$metadata, $type], $mapping);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function loadDiscriminatorColumns(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->discriminatorColumns)) {
            return;
        }

        \assert($metadata instanceof ORMClassMetadata);

        try {
            if (isset($this->discriminatorColumns[$metadata->getName()])) {
                $arrayDiscriminatorColumns = $this->discriminatorColumns[$metadata->getName()];
                if (isset($metadata->discriminatorColumn)) {
                    $arrayDiscriminatorColumns = array_merge($metadata->discriminatorColumn, $this->discriminatorColumns[$metadata->getName()]);
                }
                $metadata->setDiscriminatorColumn($arrayDiscriminatorColumns);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function loadInheritanceTypes(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->inheritanceTypes)) {
            return;
        }

        \assert($metadata instanceof ORMClassMetadata);

        try {
            if (isset($this->inheritanceTypes[$metadata->getName()])) {
                $metadata->setInheritanceType($this->inheritanceTypes[$metadata->getName()]);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function loadDiscriminators(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->discriminators)) {
            return;
        }

        \assert($metadata instanceof ORMClassMetadata);

        try {
            foreach ($this->discriminators[$metadata->getName()] as $key => $class) {
                if (\in_array($key, $metadata->discriminatorMap, true)) {
                    continue;
                }
                $metadata->setDiscriminatorMap([$key => $class]);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()), 404, $e);
        }
    }

    private function loadIndexes(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->indexes)) {
            return;
        }

        \assert($metadata instanceof ORMClassMetadata);

        foreach ($this->indexes[$metadata->getName()] as $name => $columns) {
            $metadata->table['indexes'][$name] = ['columns' => $columns];
        }
    }

    private function loadUniques(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->uniques)) {
            return;
        }

        \assert($metadata instanceof ORMClassMetadata);

        foreach ($this->uniques[$metadata->getName()] as $name => $columns) {
            $metadata->table['uniqueConstraints'][$name] = ['columns' => $columns];
        }
    }

    private function loadOverrides(ClassMetadata $metadata): void
    {
        if (!\array_key_exists($metadata->getName(), $this->overrides)) {
            return;
        }

        try {
            foreach ($this->overrides[$metadata->getName()] as $type => $overrides) {
                foreach ($overrides as $override) {
                    \call_user_func([$metadata, $type], $override['fieldName'], $override);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                sprintf('Error with class %s : %s', $metadata->getName(), $e->getMessage()),
                404,
                $e
            );
        }
    }

    private function verifyColumnNames(array $columns): void
    {
        foreach ($columns as $column) {
            if (!\is_string($column)) {
                throw new \InvalidArgumentException(sprintf('The column is not a valid string, %s given', \gettype($column)));
            }
        }
    }
}
