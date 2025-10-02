<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

final class Version20250918170500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Dropbox: link c_dropbox_file to resource_node with FK + unique index, and set integer defaults.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        if (!$sm->tablesExist(['c_dropbox_file'])) {
            return;
        }

        $table = $sm->introspectTable('c_dropbox_file');

        if (!$table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE c_dropbox_file ADD resource_node_id INT DEFAULT NULL');
            $table = $sm->introspectTable('c_dropbox_file');
        }

        $this->addSql('
        ALTER TABLE c_dropbox_file
            CHANGE c_id c_id INT DEFAULT 0 NOT NULL,
            CHANGE filesize filesize INT DEFAULT 0 NOT NULL,
            CHANGE cat_id cat_id INT DEFAULT 0 NOT NULL,
            CHANGE session_id session_id INT DEFAULT 0 NOT NULL
    ');

        $fkName = 'FK_4D71B46C1BAD783F';
        if (!$this->foreignKeyExists($sm, 'c_dropbox_file', $fkName)) {
            $this->addSql("
            ALTER TABLE c_dropbox_file
                ADD CONSTRAINT $fkName
                FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE
        ");
        }

        $uniqueName = 'UNIQ_4D71B46C1BAD783F';
        if (!$this->indexExists($table, $uniqueName)) {
            $this->addSql("CREATE UNIQUE INDEX $uniqueName ON c_dropbox_file (resource_node_id)");
        }
    }

    private function foreignKeyExists(AbstractSchemaManager $sm, string $tableName, string $fkName): bool
    {
        foreach ($sm->listTableForeignKeys($tableName) as $fk) {
            if (0 === strcasecmp($fk->getName(), $fkName)) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(Table $table, string $indexName): bool
    {
        foreach ($table->getIndexes() as $idx) {
            if (0 === strcasecmp($idx->getName(), $indexName)) {
                return true;
            }
        }

        return false;
    }

    public function down(Schema $schema): void
    {
        // Drop unique index + FK + column (best-effort rollback)
        $this->addSql('DROP INDEX UNIQ_4D71B46C1BAD783F ON c_dropbox_file');
        $this->addSql('ALTER TABLE c_dropbox_file DROP FOREIGN KEY FK_4D71B46C1BAD783F');
        $this->addSql('ALTER TABLE c_dropbox_file DROP COLUMN resource_node_id');
    }
}
