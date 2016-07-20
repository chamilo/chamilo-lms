<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Username changes
 */
class Version20150511133949 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE user ADD salt VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD username_canonical VARCHAR(100) NOT NULL');
        //$this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64992FC23A8 ON user (username_canonical)');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) NOT NULL');

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_teachers_to_create_sessions', NULL,'radio','Session','false','AllowTeachersToCreateSessionsTitle','AllowTeachersToCreateSessionsComment', NULL, NULL, 0)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_teachers_to_create_sessions', 'false', 'No')");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE user DROP salt');
        $this->addSql('DROP INDEX UNIQ_8D93D64992FC23A8 ON user');
        $this->addSql('ALTER TABLE user DROP username_canonical');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(50) NOT NULL COLLATE utf8_unicode_ci');

        $this->addSql('DELETE FROM settings_current WHERE variable = "allow_teachers_to_create_sessions" ');
        $this->addSql('DELETE FROM settings_options WHERE variable = "allow_teachers_to_create_sessions" ');
    }
}
