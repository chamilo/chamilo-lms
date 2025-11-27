<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240126222600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration resource_node --* resource_file (during development)';
    }

    public function up(Schema $schema): void
    {
        $tblResourceFile = $schema->getTable('resource_file');
        $tblResourceNode = $schema->getTable('resource_node');

        if (!$tblResourceFile->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE resource_file ADD resource_node_id INT DEFAULT NULL');

            $result = $this->connection->executeQuery('SELECT id, resource_file_id FROM resource_node');
            $resourceNodeRows = $result->fetchAllAssociative();

            foreach ($resourceNodeRows as $resourceNodeRow) {
                $this->addSql(
                    \sprintf(
                        'UPDATE resource_file SET resource_node_id = %d WHERE id = %d',
                        $resourceNodeRow['id'],
                        $resourceNodeRow['resource_file_id']
                    )
                );
            }
        }

        if (!$tblResourceFile->hasForeignKey('FK_83BF96AA1BAD783F')) {
            $this->addSql('ALTER TABLE resource_file ADD CONSTRAINT FK_83BF96AA1BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id)');
        }

        if (!$tblResourceFile->hasIndex('IDX_83BF96AA1BAD783F')) {
            $this->addSql('CREATE INDEX IDX_83BF96AA1BAD783F ON resource_file (resource_node_id)');
        }

        if ($tblResourceNode->hasForeignKey('FK_8A5F48FFCE6B9E84')) {
            $this->addSql('ALTER TABLE resource_node DROP FOREIGN KEY FK_8A5F48FFCE6B9E84');
        }

        if ($tblResourceNode->hasIndex('UNIQ_8A5F48FFCE6B9E84')) {
            $this->addSql('DROP INDEX UNIQ_8A5F48FFCE6B9E84 ON resource_node');
        }

        if ($tblResourceNode->hasColumn('resource_file_id')) {
            $this->addSql('ALTER TABLE resource_node DROP resource_file_id');
        }
    }
}
