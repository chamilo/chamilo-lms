<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Version20251205162000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create JSON template and link it for search_prefilter_prefix setting.';
    }

    public function up(Schema $schema): void
    {
        $targetVariable = 'search_prefilter_prefix';

        $templates = SettingsValueTemplateFixtures::getTemplatesGrouped();

        $foundTemplate = null;

        foreach ($templates as $category => $settings) {
            foreach ($settings as $setting) {
                if (($setting['variable'] ?? null) === $targetVariable) {
                    $foundTemplate = $setting;

                    break 2;
                }
            }
        }

        if (!$foundTemplate) {
            $this->write("No template found in SettingsValueTemplateFixtures for variable '{$targetVariable}'. Skipping.");

            return;
        }

        $jsonExample = json_encode(
            $foundTemplate['json_example'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // Check if template already exists
        $templateId = $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            [$targetVariable]
        );

        if ($templateId) {
            $this->connection->executeStatement(
                'UPDATE settings_value_template
                 SET json_example = ?, updated_at = NOW()
                 WHERE id = ?',
                [$jsonExample, $templateId]
            );
            $this->write("Updated existing template (ID {$templateId}) for variable '{$targetVariable}'.");
        } else {
            $this->connection->executeStatement(
                'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at)
                 VALUES (?, ?, NOW(), NOW())',
                [$targetVariable, $jsonExample]
            );

            $templateId = $this->connection->lastInsertId();
            $this->write("Inserted new template (ID {$templateId}) for variable '{$targetVariable}'.");
        }

        if ($templateId) {
            // Link template to existing settings rows
            $updatedRows = $this->connection->executeStatement(
                'UPDATE settings
                 SET value_template_id = ?
                 WHERE variable = ?',
                [$templateId, $targetVariable]
            );
            $this->write("Updated {$updatedRows} rows in settings for variable '{$targetVariable}'.");

            // Optional: clean legacy boolean values (Yes/No) to avoid invalid JSON
            $cleanedRows = $this->connection->executeStatement(
                "UPDATE settings
                 SET selected_value = ''
                 WHERE variable = ?
                   AND (selected_value = 'true' OR selected_value = 'false')",
                [$targetVariable]
            );
            if ($cleanedRows > 0) {
                $this->write("Cleaned legacy boolean values in {$cleanedRows} rows for variable '{$targetVariable}'.");
            }
        } else {
            $this->write("ERROR: Template ID is null for variable '{$targetVariable}'.");
        }
    }

    public function down(Schema $schema): void
    {
        $targetVariable = 'search_prefilter_prefix';

        $templateId = $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            [$targetVariable]
        );

        if (!$templateId) {
            $this->write("No template found to revert for variable '{$targetVariable}'.");

            return;
        }

        // Unlink template from settings
        $updatedRows = $this->connection->executeStatement(
            'UPDATE settings
             SET value_template_id = NULL
             WHERE value_template_id = ?',
            [$templateId]
        );
        $this->write("Unlinked template ID {$templateId} from {$updatedRows} settings rows.");

        // Delete template
        $this->connection->executeStatement(
            'DELETE FROM settings_value_template WHERE id = ?',
            [$templateId]
        );
        $this->write("Deleted template with ID {$templateId} for variable '{$targetVariable}'.");

        // Optional: reset selected_value (we do not know original Yes/No)
        $this->connection->executeStatement(
            "UPDATE settings
             SET selected_value = ''
             WHERE variable = ?",
            [$targetVariable]
        );
    }
}
