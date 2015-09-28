<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type as TableColumnType;

/**
 * Session date changes
 */
class Version20150529164400 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (!$schema->hasTable('gradebook_score_log')) {
            $gradebookScoreLog = $schema->createTable('gradebook_score_log');
            $gradebookScoreLog->addColumn(
                'id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'autoincrement' => true, 'notnull' => true]
            );
            $gradebookScoreLog->addColumn(
                'category_id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'notnull' => true]
            );
            $gradebookScoreLog->addColumn(
                'user_id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'notnull' => true]
            );
            $gradebookScoreLog->addColumn(
                'score',
                TableColumnType::FLOAT,
                ['notnull' => true, 'scale' => 0, 'precision' => 10]
            );
            $gradebookScoreLog->addColumn(
                'registered_at',
                TableColumnType::DATETIME,
                ['notnull' => true]
            );
            $gradebookScoreLog->setPrimaryKey(['id']);
            $gradebookScoreLog->addIndex(
                ['user_id'],
                'idx_gradebook_score_log_user'
            );
            $gradebookScoreLog->addIndex(
                ['user_id', 'category_id'],
                'idx_gradebook_score_log_user_category'
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('gradebook_score_log');
    }

}
