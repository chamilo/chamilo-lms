<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Session date changes
 */
class Version20150616093200 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $connection = $this->connection;
        $sql = "SELECT id FROM extra_field WHERE variable = 'video_url'";
        $result = $connection->executeQuery($sql)->fetchAll();
        if (empty($result)) {
            $this->addSql("INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible, changeable, created_at) VALUES (2, 19, 'video_url', 'VideoUrl', 1, 1, NOW())");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM extra_field
            WHERE variable = 'video_url' AND
                extra_field_type = 2 AND
                field_type = 19");
    }

}
