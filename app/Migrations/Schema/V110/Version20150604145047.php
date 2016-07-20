<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * track_e_default changes
 */
class Version20150604145047 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE track_e_default CHANGE default_event_type default_event_type VARCHAR(255) NOT NULL, CHANGE default_value_type default_value_type VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE track_e_default CHANGE default_event_type default_event_type VARCHAR(20) NOT NULL COLLATE utf8_unicode_ci, CHANGE default_value_type default_value_type VARCHAR(20) NOT NULL COLLATE utf8_unicode_ci');
    }
}
