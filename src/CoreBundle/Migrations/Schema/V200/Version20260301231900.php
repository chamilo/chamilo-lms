<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20260301231900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Ensure ai_providers setting exists and synchronize its JSON value template (aihelpers group).';
    }

    public function up(Schema $schema): void
    {
        // 1) Ensure the setting exists (do not override selected_value if already configured).
        $variable = 'ai_providers';
        $category = 'ai_helpers';
        $title = 'AI providers connection data';
        $comment = 'Configuration data to connect with external AI services.';

        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM settings
              WHERE variable = ?
                AND subkey IS NULL
                AND access_url = 1",
            [$variable]
        );

        if ($count > 0) {
            // Do not touch selected_value to avoid wiping existing JSON configuration.
            $this->connection->executeStatement(
                "UPDATE settings
                    SET title = ?,
                        comment = ?,
                        category = ?
                  WHERE variable = ?
                    AND subkey IS NULL
                    AND access_url = 1",
                [$title, $comment, $category, $variable]
            );

            $this->write("Updated setting metadata (kept selected_value intact): {$variable}");
        } else {
            // Insert with empty selected_value (admin can fill it later).
            $this->connection->executeStatement(
                "INSERT INTO settings
                    (variable, subkey, type, category, selected_value, title, comment,
                     access_url_changeable, access_url_locked, access_url)
                 VALUES
                    (?, NULL, NULL, ?, '', ?, ?, 0, 0, 1)",
                [$variable, $category, $title, $comment]
            );

            $this->write("Inserted setting: {$variable}");
        }

        // 2) Synchronize JSON template for aihelpers.ai_providers
        $groupedTemplates = SettingsValueTemplateFixtures::getTemplatesGrouped();
        $aiTemplates = $groupedTemplates['aihelpers'] ?? [];

        $templateData = null;
        foreach ($aiTemplates as $t) {
            if (($t['variable'] ?? '') === $variable) {
                $templateData = $t;
                break;
            }
        }

        if (null === $templateData) {
            $this->write("Skipped template sync: template '{$variable}' not found in SettingsValueTemplateFixtures::getTemplatesGrouped()['aihelpers'].");
            return;
        }

        $jsonExample = json_encode(
            $templateData['json_example'] ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // Create or update the template row
        $templateId = $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            [$variable]
        );

        if ($templateId) {
            $this->connection->executeStatement(
                'UPDATE settings_value_template
                    SET json_example = ?, updated_at = NOW()
                  WHERE id = ?',
                [$jsonExample, $templateId]
            );
            $this->write("Updated JSON template for '{$variable}'.");
        } else {
            $this->connection->executeStatement(
                'INSERT INTO settings_value_template
                    (variable, json_example, created_at, updated_at)
                 VALUES (?, ?, NOW(), NOW())',
                [$variable, $jsonExample]
            );
            $templateId = (int) $this->connection->lastInsertId();
            $this->write("Inserted JSON template for '{$variable}'.");
        }

        // 3) Link the template to the setting rows
        $linkedRows = $this->connection->executeStatement(
            'UPDATE settings
                SET value_template_id = ?
              WHERE variable = ?
                AND subkey IS NULL
                AND access_url = 1',
            [(int) $templateId, $variable]
        );

        $this->write("Linked {$linkedRows} settings row(s) to template '{$variable}'.");
    }

    public function down(Schema $schema): void
    {
        // Best-effort revert:
        // - Unlink template from the setting
        // - Remove the template row
        $variable = 'ai_providers';

        $this->connection->executeStatement(
            'UPDATE settings
                SET value_template_id = NULL
              WHERE variable = ?
                AND subkey IS NULL
                AND access_url = 1',
            [$variable]
        );

        $this->connection->executeStatement(
            'DELETE FROM settings_value_template WHERE variable = ?',
            [$variable]
        );

        $this->write("Unlinked and removed JSON template for '{$variable}'.");
    }
}
