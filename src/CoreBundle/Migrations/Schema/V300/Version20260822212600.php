<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Chamilo 1.11 created c_survey_question.parent_id and parent_option_id as
 * "INT NOT NULL DEFAULT 0" when survey_question_dependency was enabled.
 * Version20180319145700 only adds these columns when they are missing, so on
 * those portals they stayed NOT NULL, full of 0 and without foreign keys:
 * every new survey question (e.g. the bundled demo courses restore) then fails
 * with "Column 'parent_id' cannot be null" and closes the EntityManager.
 *
 * Ordered right before Version20260822212700 (demo courses install), which is
 * the first migration that creates survey questions through Doctrine.
 */
final class Version20260822212600 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Make c_survey_question.parent_id and parent_option_id nullable (legacy 1.11 NOT NULL DEFAULT 0 columns) and add their foreign keys.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_survey_question') || !$schema->hasTable('c_survey_question_option')) {
            return;
        }

        $table = $schema->getTable('c_survey_question');

        $this->fixColumn(
            $table,
            'parent_id',
            'c_survey_question',
            'FK_92F05EE7727ACA70',
            'IDX_92F05EE7727ACA70',
            ' ON DELETE SET NULL'
        );
        $this->fixColumn(
            $table,
            'parent_option_id',
            'c_survey_question_option',
            'FK_92F05EE7568F3281',
            'IDX_92F05EE7568F3281',
            ''
        );
    }

    public function down(Schema $schema): void {}

    private function fixColumn(
        Table $table,
        string $column,
        string $referencedTable,
        string $foreignKeyName,
        string $indexName,
        string $onDelete
    ): void {
        if (!$table->hasColumn($column)) {
            return;
        }

        if ($table->getColumn($column)->getNotnull()) {
            $this->addSql("ALTER TABLE c_survey_question MODIFY {$column} INT DEFAULT NULL");
        }

        // 0 (legacy "no parent") and dangling ids would block the foreign key.
        $this->addSql(
            "UPDATE c_survey_question q
             LEFT JOIN {$referencedTable} r ON r.iid = q.{$column}
             SET q.{$column} = NULL
             WHERE q.{$column} IS NOT NULL AND r.iid IS NULL"
        );

        $hasForeignKey = false;
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ([$column] === $foreignKey->getLocalColumns()) {
                $hasForeignKey = true;

                break;
            }
        }

        if ($hasForeignKey) {
            return;
        }

        if (!$table->hasIndex(strtolower($indexName)) && !$table->hasIndex($indexName)) {
            $this->addSql("CREATE INDEX {$indexName} ON c_survey_question ({$column})");
        }

        $this->addSql(
            "ALTER TABLE c_survey_question ADD CONSTRAINT {$foreignKeyName} FOREIGN KEY ({$column}) REFERENCES {$referencedTable} (iid){$onDelete}"
        );
    }
}
