<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;

final class Version20240811221950 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migration to consolidate schema changes and handle foreign keys, indexes, columns with proper validations.';
    }

    public function up(Schema $schema): void
    {
        $this->dropForeignKeyIfExists($schema, 'c_survey_question_option', 'FK_C4B6F5F1E27F6BF');
        $this->dropIndexIfExists($schema, 'block', 'path');
        $this->dropColumnIfExists($schema, 'c_survey_question_option', 'c_id');
        $this->dropColumnIfExists($schema, 'c_survey_question_option', 'question_option_id');

        $this->dropIndexIfExists($schema, 'course_rel_user_catalogue', 'IDX_79CA412EA76ED395');
        $this->dropIndexIfExists($schema, 'course_rel_user_catalogue', 'IDX_79CA412E91D79BD3');

        if ($this->indexExists($schema, 'gradebook_category', 'FK_96A4C705C33F7837')) {
            $this->addSql('ALTER TABLE gradebook_category DROP INDEX FK_96A4C705C33F7837, ADD UNIQUE INDEX UNIQ_96A4C705C33F7837 (document_id);');
        }

        if ($this->columnExists($schema, 'notification_event', 'title') &&
            $this->columnExists($schema, 'notification_event', 'content') &&
            $this->columnExists($schema, 'notification_event', 'link') &&
            $this->columnExists($schema, 'notification_event', 'event_type')) {
            $this->addSql('ALTER TABLE notification_event CHANGE title title VARCHAR(255) NOT NULL, CHANGE content content LONGTEXT DEFAULT NULL, CHANGE link link LONGTEXT DEFAULT NULL, CHANGE event_type event_type VARCHAR(255) NOT NULL;');
        }

        if ($this->foreignKeyExists($schema, 'c_survey_question_option', 'FK_C4B6F5F1E27F6BF')) {
            $this->addSql('ALTER TABLE c_survey_question_option ADD CONSTRAINT FK_C4B6F5F1E27F6BF FOREIGN KEY (question_id) REFERENCES c_survey_question (iid) ON DELETE SET NULL;');
        }

        $this->dropIndexIfExists($schema, 'c_blog_task_rel_user', 'user');
        $this->dropIndexIfExists($schema, 'c_blog_task_rel_user', 'task');
    }

    private function dropColumnIfExists(Schema $schema, string $tableName, string $columnName): void
    {
        if ($this->columnExists($schema, $tableName, $columnName)) {
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s;', $tableName, $columnName));
        }
    }

    private function dropIndexIfExists(Schema $schema, string $tableName, string $indexName): void
    {
        if ($this->indexExists($schema, $tableName, $indexName)) {
            $this->addSql(sprintf('DROP INDEX %s ON %s;', $indexName, $tableName));
        }
    }

    private function dropForeignKeyIfExists(Schema $schema, string $tableName, string $foreignKeyName): void
    {
        if ($this->foreignKeyExists($schema, $tableName, $foreignKeyName)) {
            $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s;', $tableName, $foreignKeyName));
        }
    }

    private function columnExists(Schema $schema, string $tableName, string $columnName): bool
    {
        return $this->connection->fetchOne(sprintf(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name='%s' AND column_name='%s';",
                $tableName,
                $columnName
            )) > 0;
    }

    private function indexExists(Schema $schema, string $tableName, string $indexName): bool
    {
        return $this->connection->fetchOne(sprintf(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND table_name='%s' AND index_name='%s';",
                $tableName,
                $indexName
            )) > 0;
    }

    private function foreignKeyExists(Schema $schema, string $tableName, string $foreignKeyName): bool
    {
        return $this->connection->fetchOne(sprintf(
                "SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE table_schema = DATABASE() AND table_name='%s' AND constraint_name='%s';",
                $tableName,
                $foreignKeyName
            )) > 0;
    }

    public function down(Schema $schema): void {}
}
