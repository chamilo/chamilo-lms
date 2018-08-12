<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Mapper;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineORMMapper implements EventSubscriber
{
    /**
     * @var array
     */
    protected $associations;

    /**
     * @var array
     */
    protected $discriminators;

    /**
     * @var array
     */
    protected $discriminatorColumns;

    /**
     * @var array
     */
    protected $inheritanceTypes;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var array
     */
    protected $indexes;

    /**
     * @var array
     */
    protected $uniques;

    /**
     * @var array
     */
    protected $overrides;

    /**
     * @param ManagerRegistry $doctrine
     * @param array           $associations
     * @param array           $indexes
     * @param array           $discriminators
     * @param array           $discriminatorColumns
     * @param array           $inheritanceTypes
     * @param array           $uniques
     * @param array           $overrides
     */
    public function __construct(ManagerRegistry $doctrine, array $associations = [], array $indexes = [], array $discriminators = [], array $discriminatorColumns = [], array $inheritanceTypes = [], array $uniques = [], array $overrides = [])
    {
        $this->doctrine = $doctrine;
        $this->associations = $associations;
        $this->indexes = $indexes;
        $this->uniques = $uniques;
        $this->discriminatorColumns = $discriminatorColumns;
        $this->discriminators = $discriminators;
        $this->inheritanceTypes = $inheritanceTypes;
        $this->overrides = $overrides;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
        ];
    }

    /**
     * @param string $class
     * @param string $field
     * @param array  $options
     */
    public function addAssociation($class, $field, array $options)
    {
        if (!isset($this->associations[$class])) {
            $this->associations[$class] = [];
        }

        $this->associations[$class][$field] = $options;
    }

    /**
     * Add a discriminator to a class.
     *
     * @param string $class              The Class
     * @param string $key                Key is the database value and values are the classes
     * @param string $discriminatorClass The mapped class
     */
    public function addDiscriminator($class, $key, $discriminatorClass)
    {
        if (!isset($this->discriminators[$class])) {
            $this->discriminators[$class] = [];
        }

        if (!isset($this->discriminators[$class][$key])) {
            $this->discriminators[$class][$key] = $discriminatorClass;
        }
    }

    /**
     * @param string $class
     * @param array  $columnDef
     */
    public function addDiscriminatorColumn($class, array $columnDef)
    {
        if (!isset($this->discriminatorColumns[$class])) {
            $this->discriminatorColumns[$class] = $columnDef;
        }
    }

    /**
     * @param string $class
     * @param string $type
     */
    public function addInheritanceType($class, $type)
    {
        if (!isset($this->inheritanceTypes[$class])) {
            $this->inheritanceTypes[$class] = $type;
        }
    }

    /**
     * @param string $class
     * @param string $name
     * @param array  $columns
     */
    public function addIndex($class, $name, array $columns)
    {
        if (!isset($this->indexes[$class])) {
            $this->indexes[$class] = [];
        }

        if (isset($this->indexes[$class][$name])) {
            return;
        }

        $this->indexes[$class][$name] = $columns;
    }

    /**
     * @param string $class
     * @param string $name
     * @param array  $columns
     */
    public function addUnique($class, $name, array $columns)
    {
        if (!isset($this->uniques[$class])) {
            $this->uniques[$class] = [];
        }

        if (isset($this->uniques[$class][$name])) {
            return;
        }

        $this->uniques[$class][$name] = $columns;
    }

    /**
     * Adds new ORM override.
     *
     * @param string $class
     * @param string $type
     * @param array  $options
     */
    final public function addOverride($class, $type, array $options)
    {
        if (!isset($this->overrides[$class])) {
            $this->overrides[$class] = [];
        }

        $this->overrides[$class][$type] = $options;
    }

    /**
     * @param $eventArgs
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
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
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    private function loadAssociations(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->associations)) {
            return;
        }

        try {
            foreach ($this->associations[$metadata->name] as $type => $mappings) {
                foreach ($mappings as $mapping) {
                    // the association is already set, skip the native one
                    if ($metadata->hasAssociation($mapping['fieldName'])) {
                        continue;
                    }

                    call_user_func([$metadata, $type], $mapping);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->name, $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    private function loadDiscriminatorColumns(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->discriminatorColumns)) {
            return;
        }

        try {
            if (isset($this->discriminatorColumns[$metadata->name])) {
                $arrayDiscriminatorColumns = $this->discriminatorColumns[$metadata->name];
                if (isset($metadata->discriminatorColumn)) {
                    $arrayDiscriminatorColumns = array_merge($metadata->discriminatorColumn, $this->discriminatorColumns[$metadata->name]);
                }
                $metadata->setDiscriminatorColumn($arrayDiscriminatorColumns);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->name, $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    private function loadInheritanceTypes(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->inheritanceTypes)) {
            return;
        }

        try {
            if (isset($this->inheritanceTypes[$metadata->name])) {
                $metadata->setInheritanceType($this->inheritanceTypes[$metadata->name]);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->name, $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    private function loadDiscriminators(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->discriminators)) {
            return;
        }

        try {
            foreach ($this->discriminators[$metadata->name] as $key => $class) {
                if (in_array($key, $metadata->discriminatorMap)) {
                    continue;
                }
                $metadata->setDiscriminatorMap([$key => $class]);
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->name, $e->getMessage()), 404, $e);
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     */
    private function loadIndexes(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->indexes)) {
            return;
        }

        foreach ($this->indexes[$metadata->name] as $name => $columns) {
            $metadata->table['indexes'][$name] = ['columns' => $columns];
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     */
    private function loadUniques(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->uniques)) {
            return;
        }

        foreach ($this->uniques[$metadata->name] as $name => $columns) {
            $metadata->table['uniqueConstraints'][$name] = ['columns' => $columns];
        }
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @throws \RuntimeException
     */
    private function loadOverrides(ClassMetadataInfo $metadata)
    {
        if (!array_key_exists($metadata->name, $this->overrides)) {
            return;
        }

        try {
            foreach ($this->overrides[$metadata->name] as $type => $overrides) {
                foreach ($overrides as $override) {
                    call_user_func([$metadata, $type], $override['fieldName'], $override);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(
                sprintf('Error with class %s : %s', $metadata->name, $e->getMessage()), 404, $e
            );
        }
    }
}
