<?php

namespace Chash\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Manages the migration to Chamilo 1.11
 * @package ChamiloLMS\Controller\Migrations
 */
class Version11 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.11.final" WHERE variable = "chamilo_database_version"');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.10" WHERE variable = "chamilo_database_version"');
    }
}
