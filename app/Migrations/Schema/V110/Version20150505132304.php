<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Extra fields changes
 */
class Version20150505132304 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Force table creation in order to do updates/insert later.
        $this->connection->executeQuery('CREATE TABLE IF NOT EXISTS extra_field (id INT AUTO_INCREMENT NOT NULL, extra_field_type INT NOT NULL, field_type INT NOT NULL, variable VARCHAR(64) NOT NULL, display_text VARCHAR(255) DEFAULT NULL, default_value LONGTEXT, field_order INT DEFAULT NULL, visible TINYINT(1) DEFAULT NULL, changeable TINYINT(1) DEFAULT NULL, filter TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->connection->executeQuery('CREATE TABLE IF NOT EXISTS extra_field_values (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(255) DEFAULT NULL, field_id INT NOT NULL, item_id INT NOT NULL, comment LONGTEXT, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->connection->executeQuery('CREATE TABLE IF NOT EXISTS extra_field_options (id INT AUTO_INCREMENT NOT NULL, field_id INT DEFAULT NULL, option_value LONGTEXT, display_text VARCHAR(64) DEFAULT NULL, option_order INT DEFAULT NULL, priority VARCHAR(255) DEFAULT NULL, priority_message VARCHAR(255) DEFAULT NULL, INDEX IDX_A572E3AE443707B0 (field_id), PRIMARY KEY(id))');

        if (!$schema->hasTable('extra_field_options')) {
            $this->connection->executeQuery(
                'ALTER TABLE extra_field_options ADD CONSTRAINT FK_A572E3AE443707B0 FOREIGN KEY (field_id) REFERENCES extra_field (id)'
            );
        }

        $this->addSql('CREATE TABLE IF NOT EXISTS extra_field_option_rel_field_option (id INT AUTO_INCREMENT NOT NULL, field_id INT DEFAULT NULL, field_option_id INT DEFAULT NULL, related_field_option_id INT DEFAULT NULL, role_id INT DEFAULT NULL, UNIQUE INDEX idx (field_id, role_id, field_option_id, related_field_option_id), PRIMARY KEY(id))');

        /*
        $this->addSql('DROP TABLE course_field');
        $this->addSql('DROP TABLE course_field_options');
        $this->addSql('DROP TABLE course_field_values');
        $this->addSql('DROP TABLE session_field');
        $this->addSql('DROP TABLE session_field_options');
        $this->addSql('DROP TABLE session_field_values');
        $this->addSql('DROP TABLE user_field');
        $this->addSql('DROP TABLE user_field_options');
        $this->addSql('DROP TABLE user_field_values');
        */
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE extra_field_options DROP FOREIGN KEY FK_A572E3AE443707B0');
        $this->addSql('DROP TABLE extra_field_option_rel_field_option');
        $this->addSql('DROP TABLE extra_field_options');
        $this->addSql('DROP TABLE extra_field');
        $this->addSql('DROP TABLE extra_field_values');
    }
}
