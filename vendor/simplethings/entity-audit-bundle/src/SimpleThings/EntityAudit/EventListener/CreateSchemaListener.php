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

namespace SimpleThings\EntityAudit\EventListener;

use Doctrine\ORM\Tools\ToolEvents;
use SimpleThings\EntityAudit\AuditManager;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\Common\EventSubscriber;

class CreateSchemaListener implements EventSubscriber
{
    /**
     * @var \SimpleThings\EntityAudit\AuditConfiguration
     */
    private $config;

    /**
     * @var \SimpleThings\EntityAudit\Metadata\MetadataFactory
     */
    private $metadataFactory;

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    public function getSubscribedEvents()
    {
        return array(
            ToolEvents::postGenerateSchemaTable,
            ToolEvents::postGenerateSchema,
        );
    }

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs)
    {
        $cm = $eventArgs->getClassMetadata();
        if ($this->metadataFactory->isAudited($cm->name)) {
            $schema = $eventArgs->getSchema();
            $entityTable = $eventArgs->getClassTable();
            $revisionTable = $schema->createTable(
                $this->config->getTablePrefix().$entityTable->getName().$this->config->getTableSuffix()
            );
            foreach ($entityTable->getColumns() AS $column) {
                /* @var $column Column */
                $revisionTable->addColumn($column->getName(), $column->getType()->getName(), array_merge(
                    $column->toArray(),
                    array('notnull' => false, 'autoincrement' => false)
                ));
            }
            $revisionTable->addColumn($this->config->getRevisionFieldName(), $this->config->getRevisionIdFieldType());
            $revisionTable->addColumn($this->config->getRevisionTypeFieldName(), 'string', array('length' => 4));
            $pkColumns = $entityTable->getPrimaryKey()->getColumns();
            $pkColumns[] = $this->config->getRevisionFieldName();
            $revisionTable->setPrimaryKey($pkColumns);
        }
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs)
    {
        $schema = $eventArgs->getSchema();
        $revisionsTable = $schema->createTable($this->config->getRevisionTableName());
        $revisionsTable->addColumn('id', $this->config->getRevisionIdFieldType(), array(
            'autoincrement' => true,
        ));
        $revisionsTable->addColumn('timestamp', 'datetime');
        $revisionsTable->addColumn('username', 'string');
        $revisionsTable->setPrimaryKey(array('id'));
    }
}
