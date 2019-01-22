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
        $table = $schema->getTable('c_lp');
        if (!$table->hasColumn('max_attempts')) {
            $this->addSql('ALTER TABLE c_lp ADD max_attempts INT NOT NULL');

        }

        if (!$table->hasColumn('subscribe_users')) {
            $this->addSql('ALTER TABLE c_lp ADD subscribe_users INT NOT NULL DEFAULT 0');
        }

        $this->addSql('ALTER TABLE c_item_property MODIFY c_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY lastedit_user_id INT NOT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY to_group_id INT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY insert_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY start_visible DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY end_visible DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_item_property MODIFY to_user_id INT NULL');
        $this->addSql("UPDATE c_item_property SET start_visible = NULL WHERE start_visible = '0000-00-00 00:00:00'");
        $this->addSql("UPDATE c_item_property SET end_visible = NULL WHERE end_visible = '0000-00-00 00:00:00'");

        // Remove inconsistencies about non-existing courses
        $this->addSql("DELETE FROM c_item_property WHERE session_id IS NOT NULL and session_id <> 0 AND session_id NOT IN (SELECT id FROM session)");
        $this->addSql("DELETE FROM c_item_property WHERE to_user_id IS NOT NULL and to_user_id <> 0 AND to_user_id NOT IN (SELECT id FROM user)");

        // Sometimes the user was deleted but we need to keep the document.
        // Taking first admin
        $this->addSql("UPDATE c_item_property SET insert_user_id = (SELECT u.user_id FROM admin a INNER JOIN user u ON (u.user_id = a.user_id AND u.active = 1) LIMIT 1) WHERE insert_user_id IS NOT NULL AND insert_user_id <> 0 AND insert_user_id NOT IN (SELECT id FROM user)");

        // Remove inconsistencies about non-existing courses
        $this->addSql("DELETE FROM c_item_property WHERE c_id NOT IN (SELECT id FROM course)");
        // Remove inconsistencies about non-existing users
        $this->addSql("DELETE FROM course_rel_user WHERE user_id NOT IN (SELECT id FROM user)");

        // Fix to_group_id
        $this->addSql("UPDATE c_item_property SET to_group_id = NULL WHERE to_group_id = 0");
        $this->addSql('UPDATE c_item_property SET to_user_id = NULL WHERE to_user_id = 0');
        $this->addSql('UPDATE c_item_property SET insert_user_id = NULL WHERE insert_user_id = 0');
        $this->addSql('UPDATE c_item_property SET session_id = NULL WHERE session_id = 0');

        $table = $schema->getTable('c_group_info');
        if ($table->hasIndex('idx_cginfo_id') == false) {
            $this->addSql('ALTER TABLE c_group_info ADD INDEX idx_cginfo_id (id);');
        }

        if ($table->hasIndex('idx_cginfo_cid') == false) {
            $this->addSql('ALTER TABLE c_group_info ADD INDEX idx_cginfo_cid (c_id);');
        }

        $table = $schema->getTable('c_item_property');
        if ($table->hasIndex('idx_cip_tgid') == false) {
            $this->addSql('ALTER TABLE c_item_property ADD INDEX idx_cip_tgid (to_group_id);');
        }

        if ($table->hasIndex('idx_cip_cid') == false) {
            $this->addSql('ALTER TABLE c_item_property ADD INDEX idx_cip_cid (c_id);');
        }

        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C18191D79BD3 FOREIGN KEY (c_id) REFERENCES course(id)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C18129F6EE60 FOREIGN KEY (to_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C1819C859CC3 FOREIGN KEY (insert_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C181613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');

        $this->addSql('CREATE INDEX IDX_1D84C18191D79BD3 ON c_item_property (c_id)');
        $this->addSql('CREATE INDEX IDX_1D84C18129F6EE60 ON c_item_property (to_user_id)');
        $this->addSql('CREATE INDEX IDX_1D84C1819C859CC3 ON c_item_property (insert_user_id)');
        $this->addSql('CREATE INDEX IDX_1D84C181613FECDF ON c_item_property (session_id)');

        // Update c_item_property.to_group_id
        $this->addSql('UPDATE c_item_property cip SET cip.to_group_id = (SELECT cgi.iid FROM c_group_info cgi WHERE cgi.c_id = cip.c_id AND cgi.id = cip.to_group_id)');
        $this->addSql("DELETE FROM c_item_property WHERE to_group_id IS NOT NULL AND to_group_id <> 0 AND to_group_id NOT IN (SELECT iid FROM c_group_info)");

        $this->addSql('ALTER TABLE c_item_property ADD CONSTRAINT FK_1D84C181330D47E9 FOREIGN KEY (to_group_id) REFERENCES c_group_info (iid)');
        $this->addSql('CREATE INDEX IDX_1D84C181330D47E9 ON c_item_property (to_group_id)');
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
