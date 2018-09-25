<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20171213092400
 *
 * Fix some missing queries for migration from 1.10 to 1.11 (GH#2214)
 * These are minor changes caused by the move from static SQL to ORM entities
 *
 * @package Application\Migrations\Schema\V111
 */
class Version20171213092400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        error_log('Version20171213092400');
        $table = $schema->getTable('extra_field_values');
        $hasIndex = $table->hasIndex('idx_efv_fiii');
        if (!$hasIndex) {
            $this->addSql('CREATE INDEX idx_efv_fiii ON extra_field_values (field_id, item_id)');
        }
        $this->addSql('ALTER TABLE language CHANGE parent_id parent_id INT DEFAULT NULL');
        $table = $schema->getTable('c_quiz_answer');
        $hasIndex = $table->hasIndex('idx_cqa_q');
        if (!$hasIndex) {
            $this->addSql('CREATE INDEX idx_cqa_q ON c_quiz_answer (question_id)');
        }
        $this->addSql('ALTER TABLE c_quiz CHANGE start_time start_time DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE c_quiz CHANGE end_time end_time DATETIME DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('c_quiz_answer');
        $hasIndex = $table->hasIndex('idx_cqa_q');
        if ($hasIndex) {
            $this->addSql('DROP INDEX idx_cqa_q ON c_quiz_answer');
        }
        $table = $schema->getTable('language');
        $this->addSql('ALTER TABLE language CHANGE parent_id parent_id TINYINT DEFAULT NULL');
        $table = $schema->getTable('extra_field_values');
        $hasIndex = $table->hasIndex('idx_efv_fiii');
        if ($hasIndex) {
            $this->addSql('DROP INDEX idx_efv_fiii ON extra_field_values');
        }
    }
}
