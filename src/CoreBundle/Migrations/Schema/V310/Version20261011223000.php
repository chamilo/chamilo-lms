<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261011223000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Allow course-level skill relations without a session.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['skill_rel_course'])) {
            return;
        }

        $table = $schemaManager->introspectTable('skill_rel_course');
        if (!$table->hasColumn('session_id') || !$table->getColumn('session_id')->getNotnull()) {
            return;
        }

        $this->connection->executeStatement(
            'ALTER TABLE skill_rel_course MODIFY session_id INT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['skill_rel_course'])) {
            return;
        }

        $table = $schemaManager->introspectTable('skill_rel_course');
        if (!$table->hasColumn('session_id') || $table->getColumn('session_id')->getNotnull()) {
            return;
        }

        $nullCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM skill_rel_course WHERE session_id IS NULL'
        );

        if ($nullCount > 0) {
            throw new RuntimeException('Cannot restore skill_rel_course.session_id to NOT NULL while course-level skill relations without a session exist.');
        }

        $this->connection->executeStatement(
            'ALTER TABLE skill_rel_course MODIFY session_id INT NOT NULL'
        );
    }
}
