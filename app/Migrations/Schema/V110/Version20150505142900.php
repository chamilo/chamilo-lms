<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20150505142900
 *
 * @package Application\Migrations\Schema\V110
 */
class Version20150505142900 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Create table for video chat
        if (!$schema->hasTable('chat_video')) {
            $chatVideoTable = $schema->createTable('chat_video');
            $chatVideoTable->addColumn(
                'id',
                Type::INTEGER,
                ['autoincrement' => true, 'notnull' => true]
            );
            $chatVideoTable->addColumn(
                'from_user',
                Type::INTEGER,
                ['notnull' => true]
            );
            $chatVideoTable->addColumn(
                'to_user',
                Type::INTEGER,
                ['notnull' => true]
            );
            $chatVideoTable->addColumn(
                'room_name',
                Type::STRING,
                ['length' => 255, 'notnull' => true]
            );
            $chatVideoTable->addColumn(
                'datetime',
                Type::DATETIME,
                ['notnull' => true]
            );
            $chatVideoTable->setPrimaryKey(['id']);
            $chatVideoTable->addIndex(
                ['from_user'],
                'idx_chat_video_from_user'
            );
            $chatVideoTable->addIndex(['to_user'], 'idx_chat_video_to_user');
            $chatVideoTable->addIndex(
                ['from_user', 'to_user'],
                'idx_chat_video_users'
            );
            $chatVideoTable->addIndex(
                ['room_name'],
                'idx_chat_video_room_name'
            );
        }
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('chat_video');
    }
}
