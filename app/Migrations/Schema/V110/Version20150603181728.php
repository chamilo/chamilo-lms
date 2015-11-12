<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Lp changes
 */
class Version20150603181728 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        $this->addSql("ALTER TABLE course ENGINE=InnoDB");
        $this->addSql("ALTER TABLE c_group_info ENGINE=InnoDB");
        $this->addSql("ALTER TABLE session ENGINE=InnoDB");
        $this->addSql("ALTER TABLE user ENGINE=InnoDB");
        $this->addSql("ALTER TABLE c_item_property ENGINE=InnoDB");
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp ADD max_attempts INT NOT NULL, ADD subscribe_users INT NOT NULL DEFAULT 0');
        $this->addSql('
            ALTER TABLE c_item_property
            MODIFY c_id INT DEFAULT NULL,
            MODIFY lastedit_user_id INT NOT NULL,
            MODIFY to_group_id INT NULL,
            MODIFY insert_user_id INT DEFAULT NULL,
            MODIFY start_visible DATETIME DEFAULT NULL,
            MODIFY end_visible DATETIME DEFAULT NULL,
            MODIFY session_id INT DEFAULT NULL,
            MODIFY to_user_id INT NULL
        ');
        $this->addSql("UPDATE c_item_property SET session_id = NULL WHERE session_id = 0");
        $this->addSql("UPDATE c_item_property SET to_group_id = NULL WHERE to_group_id = 0");
        // Remove inconsistencies about non-existing courses
        $this->addSql("DELETE FROM c_item_property WHERE c_id = 0");
        // Remove inconsistencies about non-existing users
        $this->addSql("DELETE FROM course_rel_user WHERE user_id = 0");
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C18191D79BD3 FOREIGN KEY (c_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C181330D47E9 FOREIGN KEY (to_group_id) REFERENCES c_group_info (iid)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C18129F6EE60 FOREIGN KEY (to_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C1819C859CC3 FOREIGN KEY (insert_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C181613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('CREATE INDEX IDX_1D84C18191D79BD3 ON c_item_property (c_id)');
        $this->addSql('CREATE INDEX IDX_1D84C181330D47E9 ON c_item_property (to_group_id)');
        $this->addSql('CREATE INDEX IDX_1D84C18129F6EE60 ON c_item_property (to_user_id)');
        $this->addSql('CREATE INDEX IDX_1D84C1819C859CC3 ON c_item_property (insert_user_id)');
        $this->addSql('CREATE INDEX IDX_1D84C181613FECDF ON c_item_property (session_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp DROP max_attempts, DROP subscribe_users');
        $this->addSql('ALTER TABLE c_item_property DROP FOREIGN KEY FK_1D84C18191D79BD3');
        $this->addSql('ALTER TABLE c_item_property DROP FOREIGN KEY FK_1D84C181330D47E9');
        $this->addSql('ALTER TABLE c_item_property DROP FOREIGN KEY FK_1D84C18129F6EE60');
        $this->addSql('ALTER TABLE c_item_property DROP FOREIGN KEY FK_1D84C1819C859CC3');
        $this->addSql('ALTER TABLE c_item_property DROP FOREIGN KEY FK_1D84C181613FECDF');
        $this->addSql('DROP INDEX IDX_1D84C18191D79BD3 ON c_item_property');
        $this->addSql('DROP INDEX IDX_1D84C181330D47E9 ON c_item_property');
        $this->addSql('DROP INDEX IDX_1D84C18129F6EE60 ON c_item_property');
        $this->addSql('DROP INDEX IDX_1D84C1819C859CC3 ON c_item_property');
        $this->addSql('DROP INDEX IDX_1D84C181613FECDF ON c_item_property');
        $this->addSql('ALTER TABLE c_item_property CHANGE c_id c_id INT NOT NULL, CHANGE insert_user_id insert_user_id INT NOT NULL, CHANGE session_id session_id INT NOT NULL, CHANGE start_visible start_visible DATETIME NOT NULL, CHANGE end_visible end_visible DATETIME NOT NULL');
    }
}
