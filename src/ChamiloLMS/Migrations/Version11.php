<?php

namespace ChamiloLMS\Controller\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Manages the migration to Chamilo 1.11
 * @package ChamiloLMS\Controller\Migrations
 */
class Version11 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.11" WHERE variable = "chamilo_database_version"');
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.10" WHERE variable = "chamilo_database_version"');
    }
}