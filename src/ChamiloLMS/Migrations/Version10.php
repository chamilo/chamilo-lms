<?php

namespace ChamiloLMS\Controller\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version10 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('UPDATE settings_current SET selected_value = "1.10" WHERE variable = "chamilo_database_version"');
    }

    public function down(Schema $schema)
    {

    }
}