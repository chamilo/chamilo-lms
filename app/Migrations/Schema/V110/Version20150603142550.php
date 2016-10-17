<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add branch
 */
class Version20150603142550 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        $this->addSql("ALTER TABLE c_calendar_event ENGINE=InnoDB");
        $this->addSql("ALTER TABLE c_thematic_advance ENGINE=InnoDB");
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS room (id INT AUTO_INCREMENT NOT NULL, branch_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, description LONGTEXT, geolocation VARCHAR(255) DEFAULT NULL, ip VARCHAR(39) DEFAULT NULL, ip_mask VARCHAR(6) DEFAULT NULL, INDEX IDX_729F519BDCD6CC49 (branch_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS branch_transaction_status (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS branch_transaction (id BIGINT AUTO_INCREMENT NOT NULL, status_id INT DEFAULT NULL, branch_id INT DEFAULT NULL, transaction_id BIGINT NOT NULL, action VARCHAR(20) DEFAULT NULL, item_id VARCHAR(255) DEFAULT NULL, origin VARCHAR(255) DEFAULT NULL, dest_id VARCHAR(255) DEFAULT NULL, external_info VARCHAR(255) DEFAULT NULL, time_insert DATETIME NOT NULL, time_update DATETIME NOT NULL, failed_attempts INT NOT NULL, INDEX IDX_FEFBA12B6BF700BD (status_id), INDEX IDX_FEFBA12BDCD6CC49 (branch_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS branch_sync (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, access_url_id INT NOT NULL, unique_id VARCHAR(50) NOT NULL, branch_name VARCHAR(250) NOT NULL, description LONGTEXT, branch_ip VARCHAR(40) DEFAULT NULL, latitude NUMERIC(10, 0) DEFAULT NULL, longitude NUMERIC(10, 0) DEFAULT NULL, dwn_speed INT DEFAULT NULL, up_speed INT DEFAULT NULL, delay INT DEFAULT NULL, admin_mail VARCHAR(250) DEFAULT NULL, admin_name VARCHAR(250) DEFAULT NULL, admin_phone VARCHAR(250) DEFAULT NULL, last_sync_trans_id BIGINT DEFAULT NULL, last_sync_trans_date DATETIME DEFAULT NULL, last_sync_type VARCHAR(20) DEFAULT NULL, ssl_pub_key VARCHAR(250) DEFAULT NULL, branch_type VARCHAR(250) DEFAULT NULL, lft INT DEFAULT NULL, rgt INT DEFAULT NULL, lvl INT DEFAULT NULL, root INT DEFAULT NULL, UNIQUE INDEX UNIQ_F62F45EDE3C68343 (unique_id), INDEX IDX_F62F45ED727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BDCD6CC49 FOREIGN KEY (branch_id) REFERENCES branch_sync (id)');
        $this->addSql('ALTER TABLE branch_transaction ADD CONSTRAINT FK_FEFBA12B6BF700BD FOREIGN KEY (status_id) REFERENCES branch_transaction_status (id)');
        $this->addSql('ALTER TABLE branch_transaction ADD CONSTRAINT FK_FEFBA12BDCD6CC49 FOREIGN KEY (branch_id) REFERENCES branch_sync (id)');
        $this->addSql('ALTER TABLE branch_sync ADD CONSTRAINT FK_F62F45ED727ACA70 FOREIGN KEY (parent_id) REFERENCES branch_sync (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE course ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB954177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_169E6FB954177093 ON course (room_id)');
        $this->addSql('ALTER TABLE c_calendar_event ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_calendar_event ADD CONSTRAINT FK_A062258154177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_A062258154177093 ON c_calendar_event (room_id)');
        $this->addSql('ALTER TABLE c_thematic_advance ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_thematic_advance ADD CONSTRAINT FK_62798E9754177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_62798E9754177093 ON c_thematic_advance (room_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB954177093');
        $this->addSql('ALTER TABLE c_calendar_event DROP FOREIGN KEY FK_A062258154177093');
        $this->addSql('ALTER TABLE c_thematic_advance DROP FOREIGN KEY FK_62798E9754177093');
        $this->addSql('ALTER TABLE branch_transaction DROP FOREIGN KEY FK_FEFBA12B6BF700BD');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BDCD6CC49');
        $this->addSql('ALTER TABLE branch_transaction DROP FOREIGN KEY FK_FEFBA12BDCD6CC49');
        $this->addSql('ALTER TABLE branch_sync DROP FOREIGN KEY FK_F62F45ED727ACA70');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE branch_transaction_status');
        $this->addSql('DROP TABLE branch_transaction');
        $this->addSql('DROP TABLE branch_sync');
        $this->addSql('DROP INDEX IDX_A062258154177093 ON c_calendar_event');
        $this->addSql('ALTER TABLE c_calendar_event DROP room_id');
        $this->addSql('DROP INDEX IDX_62798E9754177093 ON c_thematic_advance');
        $this->addSql('ALTER TABLE c_thematic_advance DROP room_id');
        $this->addSql('DROP INDEX IDX_169E6FB954177093 ON course');
        $this->addSql('ALTER TABLE course DROP room_id');
    }
}
