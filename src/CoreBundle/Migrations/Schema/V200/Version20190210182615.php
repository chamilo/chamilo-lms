<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Session.
 */
class Version20190210182615 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('session');
        if (false === $table->hasColumn('position')) {
            $this->addSql('ALTER TABLE session ADD COLUMN position INT DEFAULT 0 NOT NULL');
        } else {
            $this->addSql('ALTER TABLE session CHANGE position position INT DEFAULT 0 NOT NULL');
        }

        if (false === $table->hasColumn('status')) {
            $this->addSql('ALTER TABLE session ADD COLUMN status INT NOT NULL');
        }

        if (false === $table->hasForeignKey('FK_D044D5D4EF87E278')) {
            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4EF87E278 FOREIGN KEY(session_admin_id) REFERENCES user(id);');
        }

        $this->addSql('UPDATE session_category SET date_start = NULL WHERE date_start = "0000-00-00"');
        $this->addSql('UPDATE session_category SET date_end = NULL WHERE date_end = "0000-00-00"');
    }

    public function down(Schema $schema): void
    {
    }
}
