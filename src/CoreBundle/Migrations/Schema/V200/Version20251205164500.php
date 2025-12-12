<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Throwable;

final class Version20251205164500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Refactor search engine tables for Xapian: rename specific_field* and simplify search_engine_ref structure.';
    }

    public function up(Schema $schema): void
    {
        // Rename specific_field -> search_engine_field
        //    (structure remains the same: id, code, title)
        $this->write('Renaming table specific_field -> search_engine_field...');
        $this->connection->executeStatement(
            'RENAME TABLE specific_field TO search_engine_field'
        );

        // Rename specific_field_values -> search_engine_field_value
        //    and adapt its columns:
        //    - drop course_code, tool_id
        //    - rename ref_id -> resource_node_id
        $this->write('Renaming table specific_field_values -> search_engine_field_value...');
        $this->connection->executeStatement(
            'RENAME TABLE specific_field_values TO search_engine_field_value'
        );

        $this->write('Dropping legacy columns course_code, tool_id from search_engine_field_value...');
        $this->connection->executeStatement(
            'ALTER TABLE search_engine_field_value
                 DROP COLUMN course_code,
                 DROP COLUMN tool_id'
        );

        $this->write('Renaming ref_id -> resource_node_id in search_engine_field_value...');
        $this->connection->executeStatement(
            'ALTER TABLE search_engine_field_value
                 CHANGE ref_id resource_node_id INT NOT NULL'
        );

        // Simplify search_engine_ref:
        //    - drop FK to course (c_id)
        //    - drop columns c_id, tool_id, ref_id_high_level
        //    - rename ref_id_second_level -> resource_node_id
        $this->write('Updating search_engine_ref structure...');

        try {
            $this->connection->executeStatement(
                'ALTER TABLE search_engine_ref DROP FOREIGN KEY FK_473F037891D79BD3'
            );
            $this->write('Dropped foreign key FK_473F037891D79BD3 from search_engine_ref.');
        } catch (Throwable $e) {
            $this->write('Foreign key FK_473F037891D79BD3 not found on search_engine_ref, skipping FK drop.');
        }

        $this->connection->executeStatement(
            'ALTER TABLE search_engine_ref
                 DROP COLUMN c_id,
                 DROP COLUMN tool_id,
                 DROP COLUMN ref_id_high_level,
                 CHANGE ref_id_second_level resource_node_id INT DEFAULT NULL'
        );

        $this->write('search_engine_ref updated (c_id/tool_id/ref_id_high_level removed, ref_id_second_level -> resource_node_id).');
    }

    public function down(Schema $schema): void
    {
        $this->write('Reverting search_engine_ref structure...');

        // Revert search_engine_ref
        //    - rename resource_node_id -> ref_id_second_level
        //    - re-add c_id, tool_id, ref_id_high_level (as nullable/default values)
        $this->connection->executeStatement(
            'ALTER TABLE search_engine_ref
                 CHANGE resource_node_id ref_id_second_level INT DEFAULT NULL,
                 ADD c_id INT DEFAULT NULL AFTER id,
                 ADD tool_id VARCHAR(100) NOT NULL DEFAULT \'\' AFTER c_id,
                 ADD ref_id_high_level INT NOT NULL DEFAULT 0 AFTER tool_id'
        );

        try {
            $this->connection->executeStatement(
                'CREATE INDEX IDX_473F037891D79BD3 ON search_engine_ref (c_id)'
            );
        } catch (Throwable $e) {
            $this->write('Could not recreate index IDX_473F037891D79BD3 on search_engine_ref: '.$e->getMessage());
        }

        try {
            $this->connection->executeStatement(
                'ALTER TABLE search_engine_ref
                     ADD CONSTRAINT FK_473F037891D79BD3
                     FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE SET NULL'
            );
        } catch (Throwable $e) {
            $this->write('Could not recreate foreign key FK_473F037891D79BD3 on search_engine_ref: '.$e->getMessage());
        }

        // Revert search_engine_field_value
        $this->write('Reverting search_engine_field_value -> specific_field_values...');

        // resource_node_id -> ref_id
        $this->connection->executeStatement(
            'ALTER TABLE search_engine_field_value
                 CHANGE resource_node_id ref_id INT NOT NULL'
        );

        $this->connection->executeStatement(
            "ALTER TABLE search_engine_field_value
                 ADD course_code VARCHAR(40) NOT NULL DEFAULT '',
                 ADD tool_id VARCHAR(100) NOT NULL DEFAULT ''"
        );

        $this->connection->executeStatement(
            'RENAME TABLE search_engine_field_value TO specific_field_values'
        );

        // Revert search_engine_field -> specific_field
        $this->write('Reverting search_engine_field -> specific_field...');

        $this->connection->executeStatement(
            'RENAME TABLE search_engine_field TO specific_field'
        );
    }
}
