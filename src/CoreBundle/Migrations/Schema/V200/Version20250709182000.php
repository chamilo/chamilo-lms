<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250709182000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Renames column name to variable in settings_value_template table.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Check column exists
        $columns = $schemaManager->listTableColumns('settings_value_template');

        if (isset($columns['name'])) {
            $this->addSql("
                ALTER TABLE settings_value_template
                CHANGE COLUMN name variable VARCHAR(190) NOT NULL;
            ");
            $this->write("Renamed column name → variable in settings_value_template.");
        }

        // Drop old index and recreate with new name
        $indexes = $schemaManager->listTableIndexes('settings_value_template');
        if (isset($indexes['UNIQ_settings_value_template_name'])) {
            $this->addSql("
                ALTER TABLE settings_value_template
                DROP INDEX UNIQ_settings_value_template_name,
                ADD UNIQUE INDEX UNIQ_settings_value_template_variable (variable);
            ");
            $this->write("Recreated unique index for variable column.");
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $columns = $schemaManager->listTableColumns('settings_value_template');

        if (isset($columns['variable'])) {
            $this->addSql("
                ALTER TABLE settings_value_template
                CHANGE COLUMN variable name VARCHAR(190) NOT NULL;
            ");
            $this->write("Renamed column variable → name in settings_value_template.");
        }

        $indexes = $schemaManager->listTableIndexes('settings_value_template');
        if (isset($indexes['UNIQ_settings_value_template_variable'])) {
            $this->addSql("
                ALTER TABLE settings_value_template
                DROP INDEX UNIQ_settings_value_template_variable,
                ADD UNIQUE INDEX UNIQ_settings_value_template_name (name);
            ");
            $this->write("Recreated unique index for name column.");
        }
    }
}
