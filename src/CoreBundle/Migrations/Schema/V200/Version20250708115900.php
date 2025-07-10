<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Version20250708115900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Populate settings_value_template table from SettingsValueTemplateFixtures and update settings.value_template_id accordingly.';
    }

    public function up(Schema $schema): void
    {
        $templates = SettingsValueTemplateFixtures::getTemplatesGrouped();

        foreach ($templates as $category => $settings) {
            foreach ($settings as $setting) {
                $variable = $setting['variable'];
                $jsonExample = json_encode(
                    $setting['json_example'],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );

                // Check if template already exists
                $templateId = $this->connection->fetchOne(
                    'SELECT id FROM settings_value_template WHERE variable = ?',
                    [$variable]
                );

                if ($templateId) {
                    $this->connection->executeStatement(
                        'UPDATE settings_value_template SET json_example = ?, updated_at = NOW() WHERE id = ?',
                        [$jsonExample, $templateId]
                    );
                } else {
                    $this->connection->executeStatement(
                        'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at) VALUES (?, ?, NOW(), NOW())',
                        [$variable, $jsonExample]
                    );

                    $templateId = $this->connection->lastInsertId();
                }

                if ($templateId) {
                    $updatedRows = $this->connection->executeStatement(
                        'UPDATE settings
                         SET value_template_id = ?
                         WHERE variable = ?',
                        [$templateId, $variable]
                    );
                    $this->write("Updated {$updatedRows} rows in settings for variable '{$variable}'");
                } else {
                    echo "[DEBUG] ERROR: Template ID still NULL for variable={$variable}\n";
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $templates = SettingsValueTemplateFixtures::getTemplatesGrouped();

        foreach ($templates as $category => $settings) {
            foreach ($settings as $setting) {
                $variable = $setting['variable'];

                $templateId = $this->connection->fetchOne(
                    'SELECT id FROM settings_value_template WHERE variable = ?',
                    [$variable]
                );

                if ($templateId) {
                    $this->connection->executeStatement(
                        'UPDATE settings
                         SET value_template_id = NULL
                         WHERE value_template_id = ?',
                        [$templateId]
                    );

                    $this->connection->executeStatement(
                        'DELETE FROM settings_value_template WHERE id = ?',
                        [$templateId]
                    );
                    $this->write("Deleted template with ID {$templateId} for variable {$variable}");
                }
            }
        }
    }
}
