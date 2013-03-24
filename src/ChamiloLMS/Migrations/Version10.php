<?php

namespace ChamiloLMS\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Manages the migration to Chamilo 1.10
 * @package ChamiloLMS\Controller\Migrations
 */
class Version10 extends AbstractMigration
{
    /**
     * Chamilo upgrade
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE settings_current SET selected_value = '1.10' WHERE variable = 'chamilo_database_version'");

        //@todo change this into a function
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_web_profiler', NULL,'radio','Portal','false','AllowTeachersToCreateSessionsTitle','AllowTeachersToCreateSessionsComment', NULL, NULL, 0)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_web_profiler', 'true', 'Yes')");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_web_profiler', 'false', 'No')");
    }

    /**
     * Chamilo downgrade
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("UPDATE settings_current SET selected_value = '1.9' WHERE variable = 'chamilo_database_version'");
        $this->addSql("DELETE FROM settings_current WHERE variable = 'allow_web_profiler'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'allow_web_profiler'");
    }
}