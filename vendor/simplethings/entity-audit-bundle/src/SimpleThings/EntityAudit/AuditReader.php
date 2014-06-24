<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;
use SimpleThings\EntityAudit\Utils\ArrayDiff;

class AuditReader
{
    private $em;

    private $config;

    private $metadataFactory;

    /**
     * @param EntityManager $em
     * @param AuditConfiguration $config
     * @param MetadataFactory $factory
     */
    public function __construct(EntityManager $em, AuditConfiguration $config, MetadataFactory $factory)
    {
        $this->em = $em;
        $this->config = $config;
        $this->metadataFactory = $factory;
        $this->platform = $this->em->getConnection()->getDatabasePlatform();
    }

    /**
     * Find a class at the specific revision.
     *
     * This method does not require the revision to be exact but it also searches for an earlier revision
     * of this entity and always returns the latest revision below or equal the given revision
     *
     * @param string $className
     * @param mixed $id
     * @param int $revision
     * @return object
     */
    public function find($className, $id, $revision)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw AuditException::notAudited($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL  = "e." . $this->config->getRevisionFieldName() ." <= ?";

        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                $columnName = $class->fieldMappings[$idField]['columnName'];
            } else if (isset($class->associationMappings[$idField])) {
                $columnName = $class->associationMappings[$idField]['joinColumns'][0];
            }

            $whereSQL .= " AND " . $columnName . " = ?";
        }

        $columnList = "";
        $columnMap  = array();

        foreach ($class->fieldNames as $columnName => $field) {
            if ($columnList) {
                $columnList .= ', ';
            }

            $type = Type::getType($class->fieldMappings[$field]['type']);
            $columnList .= $type->convertToPHPValueSQL(
                $class->getQuotedColumnName($field, $this->platform), $this->platform) .' AS ' . $field;
            $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
        }

        foreach ($class->associationMappings AS $assoc) {
            if ( ($assoc['type'] & ClassMetadata::TO_ONE) == 0 || !$assoc['isOwningSide']) {
                continue;
            }

            foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                if ($columnList) {
                    $columnList .= ', ';
                }

                $columnList .= $sourceCol;
                $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
            }
        }

        $values = array_merge(array($revision), array_values($id));

        $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL . " ORDER BY e.rev DESC";
        $row = $this->em->getConnection()->fetchAssoc($query, $values);

        if (!$row) {
            throw AuditException::noRevisionFound($class->name, $id, $revision);
        }

        return $this->createEntity($class->name, $row);
    }

    /**
     * Simplified and stolen code from UnitOfWork::createEntity.
     *
     * NOTICE: Creates an old version of the entity, HOWEVER related associations are all managed entities!!
     *
     * @param string $className
     * @param array $data
     * @return object
     */
    private function createEntity($className, array $data)
    {
        $class = $this->em->getClassMetadata($className);
        $entity = $class->newInstance();

        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $value = $type->convertToPHPValue($value, $this->platform);
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        foreach ($class->associationMappings as $field => $assoc) {
            // Check if the association is not among the fetch-joined associations already.
            if (isset($hints['fetched'][$className][$field])) {
                continue;
            }

            $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

            if ($assoc['type'] & ClassMetadata::TO_ONE) {
                if ($assoc['isOwningSide']) {
                    $associatedId = array();
                    foreach ($assoc['targetToSourceKeyColumns'] as $targetColumn => $srcColumn) {
                        $joinColumnValue = isset($data[$srcColumn]) ? $data[$srcColumn] : null;
                        if ($joinColumnValue !== null) {
                            $associatedId[$targetClass->fieldNames[$targetColumn]] = $joinColumnValue;
                        }
                    }
                    if ( ! $associatedId) {
                        // Foreign key is NULL
                        $class->reflFields[$field]->setValue($entity, null);
                    } else {
                        $associatedEntity = $this->em->getReference($targetClass->name, $associatedId);
                        $class->reflFields[$field]->setValue($entity, $associatedEntity);
                    }
                } else {
                    // Inverse side of x-to-one can never be lazy
                    $class->reflFields[$field]->setValue($entity, $this->getEntityPersister($assoc['targetEntity'])
                            ->loadOneToOneEntity($assoc, $entity));
                }
            } else {
                // Inject collection
                $reflField = $class->reflFields[$field];
                $reflField->setValue($entity, new ArrayCollection);
            }
        }

        return $entity;
    }

    /**
     * Return a list of all revisions.
     *
     * @param int $limit
     * @param int $offset
     * @return Revision[]
     */
    public function findRevisionHistory($limit = 20, $offset = 0)
    {
        $this->platform = $this->em->getConnection()->getDatabasePlatform();

        $query = $this->platform->modifyLimitQuery(
            "SELECT * FROM " . $this->config->getRevisionTableName() . " ORDER BY id DESC", $limit, $offset
        );
        $revisionsData = $this->em->getConnection()->fetchAll($query);

        $revisions = array();
        foreach ($revisionsData AS $row) {
            $revisions[] = new Revision(
                $row['id'],
                \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $row['timestamp']),
                $row['username']
            );
        }
        return $revisions;
    }

    /**
     * @deprecated this function name is misspelled.
     * Suggest using findEntitiesChangedAtRevision instead.
     */
    public function findEntitesChangedAtRevision($revision)
    {
        return $this->findEntitiesChangedAtRevision($revision);
    }

    /**
     * Return a list of ChangedEntity instances created at the given revision.
     *
     * @param int $revision
     * @return ChangedEntity[]
     */
    public function findEntitiesChangedAtRevision($revision)
    {
        $auditedEntities = $this->metadataFactory->getAllClassNames();

        $changedEntities = array();
        foreach ($auditedEntities AS $className) {
            $class = $this->em->getClassMetadata($className);
            $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

            $whereSQL   = "e." . $this->config->getRevisionFieldName() ." = ?";
            $columnList = "e." . $this->config->getRevisionTypeFieldName();
            $columnMap  = array();

            foreach ($class->fieldNames as $columnName => $field) {
                $type = Type::getType($class->fieldMappings[$field]['type']);
                $columnList .= ', ' . $type->convertToPHPValueSQL(
                    $class->getQuotedColumnName($field, $this->platform), $this->platform) . ' AS ' . $field;
                $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
            }

            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $columnList .= ', ' . $sourceCol;
                        $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
                    }
                }
            }

            $this->platform = $this->em->getConnection()->getDatabasePlatform();
            $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL;
            $revisionsData = $this->em->getConnection()->executeQuery($query, array($revision));

            foreach ($revisionsData AS $row) {
                $id   = array();
                $data = array();

                foreach ($class->identifier AS $idField) {
                    $id[$idField] = $row[$idField];
                }

                $entity = $this->createEntity($className, $row);
                $changedEntities[] = new ChangedEntity($className, $id, $row[$this->config->getRevisionTypeFieldName()], $entity);
            }
        }
        return $changedEntities;
    }

    /**
     * Return the revision object for a particular revision.
     *
     * @param  int $rev
     * @return Revision
     */
    public function findRevision($rev)
    {
        $query = "SELECT * FROM " . $this->config->getRevisionTableName() . " r WHERE r.id = ?";
        $revisionsData = $this->em->getConnection()->fetchAll($query, array($rev));

        if (count($revisionsData) == 1) {
            return new Revision(
                $revisionsData[0]['id'],
                \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $revisionsData[0]['timestamp']),
                $revisionsData[0]['username']
            );
        } else {
            throw AuditException::invalidRevision($rev);
        }
    }

    /**
     * Find all revisions that were made of entity class with given id.
     *
     * @param string $className
     * @param mixed $id
     * @return Revision[]
     */
    public function findRevisions($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw AuditException::notAudited($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL = "";
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->fieldMappings[$idField]['columnName'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }

        $query = "SELECT r.* FROM " . $this->config->getRevisionTableName() . " r " .
                 "INNER JOIN " . $tableName . " e ON r.id = e." . $this->config->getRevisionFieldName() . " WHERE " . $whereSQL . " ORDER BY r.id DESC";
        $revisionsData = $this->em->getConnection()->fetchAll($query, array_values($id));

        $revisions = array();
        $this->platform = $this->em->getConnection()->getDatabasePlatform();
        foreach ($revisionsData AS $row) {
            $revisions[] = new Revision(
                $row['id'],
                \DateTime::createFromFormat($this->platform->getDateTimeFormatString(), $row['timestamp']),
                $row['username']
            );
        }

        return $revisions;
    }

    /**
     * Gets the current revision of the entity with given ID.
     *
     * @param string $className
     * @param mixed $id
     * @return integer
     */
    public function getCurrentRevision($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw AuditException::notAudited($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereSQL = "";
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->fieldMappings[$idField]['columnName'] . " = ?";
            } else if (isset($class->associationMappings[$idField])) {
                if ($whereSQL) {
                    $whereSQL .= " AND ";
                }
                $whereSQL .= "e." . $class->associationMappings[$idField]['joinColumns'][0] . " = ?";
            }
        }

        $query = "SELECT e.".$this->config->getRevisionFieldName()." FROM " . $tableName . " e " .
                        " WHERE " . $whereSQL . " ORDER BY e.".$this->config->getRevisionFieldName()." DESC";
        $revision = $this->em->getConnection()->fetchColumn($query, array_values($id));

        return $revision;
    }

    protected function getEntityPersister($entity)
    {
        $uow = $this->em->getUnitOfWork();
        return $uow->getEntityPersister($entity);
    }

    /**
     * Get an array with the differences of between two specific revisions of
     * an object with a given id.
     *
     * @param string $className
     * @param int $id
     * @param int $oldRevision
     * @param int $newRevision
     * @return array
     */
    public function diff($className, $id, $oldRevision, $newRevision)
    {
        $oldObject = $this->find($className, $id, $oldRevision);
        $newObject = $this->find($className, $id, $newRevision);
        
        $oldValues = $this->getEntityValues($className, $oldObject);
        $newValues = $this->getEntityValues($className, $newObject);

        $differ = new ArrayDiff();
        return $differ->diff($oldValues, $newValues);
    }

    /**
     * Get the values for a specific entity as an associative array
     *
     * @param string $className
     * @param object $entity
     * @return array
     */
    public function getEntityValues($className, $entity)
    {
        $metadata = $this->em->getClassMetadata($className);
        $fields = $metadata->getFieldNames();

        $return = array();
        foreach ($fields AS $fieldName) {
            $return[$fieldName] = $metadata->getFieldValue($entity, $fieldName);
        }

        return $return;
    }

    public function getEntityHistory($className, $id)
    {
        if (!$this->metadataFactory->isAudited($className)) {
            throw AuditException::notAudited($className);
        }

        $class = $this->em->getClassMetadata($className);
        $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();

        if (!is_array($id)) {
            $id = array($class->identifier[0] => $id);
        }

        $whereId = array();
        foreach ($class->identifier AS $idField) {
            if (isset($class->fieldMappings[$idField])) {
                $columnName = $class->fieldMappings[$idField]['columnName'];
            } else if (isset($class->associationMappings[$idField])) {
                $columnName = $class->associationMappings[$idField]['joinColumns'][0];
            } else {
                continue;
            }

            $whereId[] = "{$columnName} = ?";
        }

        $whereSQL  = implode(' AND ', $whereId);
        $columnList = "";
        $columnMap  = array();

        foreach ($class->fieldNames as $columnName => $field) {
            if ($columnList) {
                $columnList .= ', ';
            }

            $type = Type::getType($class->fieldMappings[$field]['type']);
            $columnList .= $type->convertToPHPValueSQL(
                               $class->getQuotedColumnName($field, $this->platform), $this->platform) .' AS ' . $field;
            $columnMap[$field] = $this->platform->getSQLResultCasing($columnName);
        }

        foreach ($class->associationMappings AS $assoc) {
            if ( ($assoc['type'] & ClassMetadata::TO_ONE) == 0 || !$assoc['isOwningSide']) {
                continue;
            }

            foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                if ($columnList) {
                    $columnList .= ', ';
                }

                $columnList .= $sourceCol;
                $columnMap[$sourceCol] = $this->platform->getSQLResultCasing($sourceCol);
            }
        }

        $values = array_values($id);

        $query = "SELECT " . $columnList . " FROM " . $tableName . " e WHERE " . $whereSQL . " ORDER BY e.rev DESC";
        $stmt = $this->em->getConnection()->executeQuery($query, $values);

        $result = array();
        while ($row = $stmt->fetch(Query::HYDRATE_ARRAY)) {
            $result[] = $this->createEntity($class->name, $row);
        }

        return $result;
    }
}
