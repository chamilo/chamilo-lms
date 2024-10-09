<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

final class Version20240928003000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add new fields to session and c_lp tables for handling reinscription and session repetition logic';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Add fields to the 'session' table
        if ($schemaManager->tablesExist('session')) {
            $sessionTable = $schemaManager->listTableColumns('session');

            if (!isset($sessionTable['parent_id'])) {
                $this->addSql("ALTER TABLE session ADD parent_id INT DEFAULT NULL");
            }
            if (!isset($sessionTable['days_to_reinscription'])) {
                $this->addSql("ALTER TABLE session ADD days_to_reinscription INT DEFAULT NULL");
            }
            if (!isset($sessionTable['last_repetition'])) {
                $this->addSql("ALTER TABLE session ADD last_repetition TINYINT(1) DEFAULT 0 NOT NULL");
            }
            if (!isset($sessionTable['days_to_new_repetition'])) {
                $this->addSql("ALTER TABLE session ADD days_to_new_repetition INT DEFAULT NULL");
            }
        }

        // Add the field to the 'c_lp' (Learnpath) table
        if ($schemaManager->tablesExist('c_lp')) {
            $clpTable = $schemaManager->listTableColumns('c_lp');

            if (!isset($clpTable['validity_in_days'])) {
                $this->addSql("ALTER TABLE c_lp ADD validity_in_days INT DEFAULT NULL");
            }
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Revert changes in the 'session' table
        if ($schemaManager->tablesExist('session')) {
            $sessionTable = $schemaManager->listTableColumns('session');

            if (isset($sessionTable['parent_id'])) {
                $this->addSql("ALTER TABLE session DROP COLUMN parent_id");
            }
            if (isset($sessionTable['days_to_reinscription'])) {
                $this->addSql("ALTER TABLE session DROP COLUMN days_to_reinscription");
            }
            if (isset($sessionTable['last_repetition'])) {
                $this->addSql("ALTER TABLE session DROP COLUMN last_repetition");
            }
            if (isset($sessionTable['days_to_new_repetition'])) {
                $this->addSql("ALTER TABLE session DROP COLUMN days_to_new_repetition");
            }
        }

        // Revert changes in the 'c_lp' table
        if ($schemaManager->tablesExist('c_lp')) {
            $clpTable = $schemaManager->listTableColumns('c_lp');

            if (isset($clpTable['validity_in_days'])) {
                $this->addSql("ALTER TABLE c_lp DROP COLUMN validity_in_days");
            }
        }
    }
}
