<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type as TableColumnType;

/**
 * Session date changes
 */
class Version20150608104600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if (!$schema->hasTable('extra_field_rel_tag')) {
            $extraFieldRelTag = $schema->createTable('extra_field_rel_tag');
            $extraFieldRelTag->addColumn(
                'id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'autoincrement' => true, 'notnull' => true]
            );
            $extraFieldRelTag->addColumn(
                'field_id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'notnull' => true]
            );
            $extraFieldRelTag->addColumn(
                'item_id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'notnull' => true]
            );
            $extraFieldRelTag->addColumn(
                'tag_id',
                TableColumnType::INTEGER,
                ['unsigned' => true, 'notnull' => true]
            );
            $extraFieldRelTag->setPrimaryKey(['id']);
            $extraFieldRelTag->addIndex(
                ['field_id'],
                'idx_frt_field'
            );
            $extraFieldRelTag->addIndex(
                ['item_id'],
                'idx_frt_item'
            );
            $extraFieldRelTag->addIndex(
                ['tag_id'],
                'idx_frt_tag'
            );
            $extraFieldRelTag->addIndex(
                ['field_id', 'item_id', 'tag_id'],
                'idx_frt_field_item_tag'
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('extra_field_rel_tag');
    }

}
