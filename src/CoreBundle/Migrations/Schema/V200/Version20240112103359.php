<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240112103359 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds value_template_id column to settings table and creates settings_value_template table for JSON templates.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Create table settings_value_template
        if (!$schemaManager->tablesExist(['settings_value_template'])) {
            $this->addSql("
                CREATE TABLE settings_value_template (
                    id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                    variable VARCHAR(190) NOT NULL,
                    description LONGTEXT DEFAULT NULL,
                    json_example LONGTEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                    updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                    UNIQUE INDEX UNIQ_settings_value_template_variable (variable),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;
            ");
            $this->write('Created table settings_value_template.');
        }

        // Add column value_template_id to settings
        $columns = $schemaManager->listTableColumns('settings');
        if (!isset($columns['value_template_id'])) {
            $this->addSql('
                ALTER TABLE settings ADD value_template_id INT UNSIGNED DEFAULT NULL;
            ');
            $this->write('Added value_template_id column to settings table.');
        }

        // Add FK constraint
        $foreignKeys = $schemaManager->listTableForeignKeys('settings');
        $fkExists = false;
        foreach ($foreignKeys as $fk) {
            if (\in_array('value_template_id', $fk->getLocalColumns(), true)) {
                $fkExists = true;

                break;
            }
        }

        if (!$fkExists) {
            $this->addSql('
                ALTER TABLE settings
                ADD CONSTRAINT FK_E545A0C5C72FB79B
                FOREIGN KEY (value_template_id) REFERENCES settings_value_template (id) ON DELETE SET NULL;
            ');
            $this->write('Added foreign key constraint from settings to settings_value_template.');

            $this->addSql('
                CREATE INDEX IDX_E545A0C5C72FB79B ON settings (value_template_id);
            ');
            $this->write('Created index IDX_E545A0C5C72FB79B on settings.value_template_id.');
        }
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['settings'])) {
            $foreignKeys = $schemaManager->listTableForeignKeys('settings');
            foreach ($foreignKeys as $fk) {
                if (\in_array('value_template_id', $fk->getLocalColumns(), true)) {
                    $this->addSql(\sprintf(
                        'ALTER TABLE settings DROP FOREIGN KEY %s',
                        $fk->getName()
                    ));
                    $this->write('Dropped foreign key from settings to settings_value_template.');
                }
            }

            $this->addSql('
                DROP INDEX IF EXISTS IDX_E545A0C5C72FB79B ON settings;
            ');
            $this->write('Dropped index IDX_E545A0C5C72FB79B on settings.');
        }

        $columns = $schemaManager->listTableColumns('settings');
        if (isset($columns['value_template_id'])) {
            $this->addSql('
                ALTER TABLE settings DROP COLUMN value_template_id;
            ');
            $this->write('Dropped value_template_id column from settings table.');
        }

        if ($schemaManager->tablesExist(['settings_value_template'])) {
            $this->addSql('DROP TABLE settings_value_template');
            $this->write('Dropped table settings_value_template.');
        }
    }
}
