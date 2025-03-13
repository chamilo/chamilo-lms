<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250306101000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate data from the settings table to the new plugin table.';
    }

    public function up(Schema $schema): void
    {
        // Insert unique plugin data from settings to plugin
        $this->addSql("
            INSERT INTO plugin (title, installed, active, version, access_url_id, configuration, source)
            SELECT
                subkey AS title,
                MAX(IF(variable = 'status', 1, 0)) AS installed,
                MAX(IF(selected_value = 'true', 1, 0)) AS active,
                '1.0.0' AS version,
                access_url AS access_url_id,
                '{}' AS configuration,
                'third_party' AS source
            FROM settings
            WHERE category = 'plugins'
            GROUP BY subkey;
        ");
    }

    public function down(Schema $schema): void
    {
        // Restore data back to settings if rolling back
        $this->addSql("
            INSERT INTO settings (variable, subkey, type, category, selected_value, title, access_url)
            SELECT
                'status' AS variable,
                title AS subkey,
                'setting' AS type,
                'plugins' AS category,
                IF(active = 1, 'true', 'false') AS selected_value,
                title AS title,
                access_url_id AS access_url
            FROM plugin;
        ");
    }
}
