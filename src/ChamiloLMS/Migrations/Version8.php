<?php

namespace ChamiloLMS\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;


/**
 * Manages the migration to version 1.8.0
 * @package ChamiloLMS\Controller\Migrations
 */
class Version8 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.8.8.14911.doc" WHERE variable = "chamilo_database_version"');
    }

    public function down(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.8.7" WHERE variable = "chamilo_database_version"');
    }
}
