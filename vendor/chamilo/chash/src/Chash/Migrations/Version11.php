<?php

namespace Chash\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Manages the migration to Chamilo 11
 * @package ChamiloLMS\Controller\Migrations
 */
class Version11 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $sql = 'UPDATE settings_current SET selected_value = "11"
                WHERE variable = "chamilo_database_version"';
        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $sql = 'UPDATE settings_current SET selected_value = "10"
                WHERE variable = "chamilo_database_version"';
        $this->addSql($sql);
    }

    public function preUp(Schema $schema)
    {
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
    public function preDown(Schema $schema)
    {
    }

    /**
     * @param Schema $schema
     */
    public function postDown(Schema $schema)
    {
    }
}
