<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Calendar color
 */
class Version20151101082200 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE access_url ENGINE=InnoDB");
        $this->addSql("ALTER TABLE access_url_rel_course ENGINE=InnoDB");
        $this->addSql("ALTER TABLE course_rel_user ENGINE=InnoDB");
        $this->addSql("ALTER TABLE session_category ENGINE=InnoDB");
        $this->addSql("ALTER TABLE settings_current ENGINE=InnoDB");
        $this->addSql("ALTER TABLE settings_options ENGINE=InnoDB");
        $this->addSql("ALTER TABLE usergroup ENGINE=InnoDB");
        $this->addSql("ALTER TABLE usergroup_rel_user ENGINE=InnoDB");

        $this->addSql("ALTER TABLE session_rel_course DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE session_rel_course_rel_user DROP PRIMARY KEY");
        $this->addSql("ALTER TABLE session MODIFY session_category_id INT NULL");

        $this->addSql("UPDATE session SET session_category_id = NULL WHERE session_category_id NOT IN (SELECT id FROM session_category)");

        $table = $schema->getTable('session_rel_course_rel_user');
        if ($table->hasForeignKey('FK_720167E91D79BD3')) {
            $this->addSql("ALTER TABLE session_rel_course_rel_user DROP FOREIGN KEY FK_720167E91D79BD3");
        }

        $table = $schema->getTable('session_rel_course');
        if ($table->hasForeignKey('FK_12D110D391D79BD3')) {
            $this->addSql("ALTER TABLE session_rel_course DROP FOREIGN KEY FK_12D110D391D79BD3");
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
