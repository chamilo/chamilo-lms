<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20180904175500.
 *
 * Add foreign key from message
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20180904175500 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE INDEX IDX_B68FF524537A1329 ON message_attachment (message_id)');
        $this->addSql('ALTER TABLE message_attachment CHANGE message_id message_id BIGINT NOT NULL');
        $this->addSql('ALTER TABLE message_attachment ADD CONSTRAINT FK_B68FF524537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
