<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\EasyExtendsBundle\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineORMMapper implements EventSubscriber
{
    protected $associations;

    protected $discriminators;

    protected $discriminatorColumns;

    protected $inheritanceTypes;

    protected $doctrine;

    protected $indexes;

    protected $uniques;

    /**
     * @param ManagerRegistry $doctrine
     * @param array           $associations
     * @param array           $indexes
     * @param array           $discriminators
     * @param array           $discriminatorColumns
     * @param array           $inheritanceTypes
     * @param array           $uniques
     */
    public function __construct(ManagerRegistry $doctrine, array $associations = array(), array $indexes = array(), array $discriminators = array(), array $discriminatorColumns = array(), array $inheritanceTypes = array(), array $uniques = array())
    {
        $this->doctrine = $doctrine;
        $this->associations = $associations;
        $this->indexes = $indexes;
        $this->uniques = $uniques;
        $this->discriminatorColumns = $discriminatorColumns;
        $this->discriminators = $discriminators;
        $this->inheritanceTypes = $inheritanceTypes;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata'
        );
    }

    /**
     * @param string $class
     * @param string $field
     * @param array  $options
     */
    public function addAssociation($class, $field, array $options)
    {
        if (!isset($this->associations[$class])) {
            $this->associations[$class] = array();
        }

        $this->associations[$class][$field] = $options;
    }

    /**
     * Add a discriminator to a class.
     *
     * @param string $class               The Class
     * @param string $key                 Key is the database value and values are the classes
     * @param string $discriminatorClass  The mapped class
     *
     * @return void
     */
    public function addDiscriminator($class, $key, $discriminatorClass)
    {
        if (!isset($this->discriminators[$class])) {
            $this->discriminators[$class] = array();
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
            $this->indexes[$class] = array();
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
            $this->uniques[$class] = array();
        }

        if (isset($this->uniques[$class][$name])) {
            return;
        }

        $this->uniques[$class][$name] = $columns;
    }

    /**
     * @param $eventArgs
     */
    public function loadClassMetadata($eventArgs)
    {
        $metadata = $eventArgs->getClassMetadata();

        $this->loadAssociations($metadata);
        $this->loadIndexes($metadata);
        $this->loadUniques($metadata);

        $this->loadDiscriminatorColumns($metadata);
        $this->loadDiscriminators($metadata);
        $this->loadInheritanceTypes($metadata);
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

                    call_user_func(array($metadata, $type), $mapping);
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
                $metadata->setDiscriminatorMap(array($key=>$class));
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
            $metadata->table['indexes'][$name] = array('columns' => $columns);
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
            $metadata->table['uniqueConstraints'][$name] = array('columns' => $columns);
        }
    }
}
