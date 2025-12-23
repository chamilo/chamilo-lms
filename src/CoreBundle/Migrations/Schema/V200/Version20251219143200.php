<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20251219143200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update settings_value_template JSON example for a single template and link it to settings rows (no deletes).';
    }

    public function up(Schema $schema): void
    {
        $targetVariable = 'score_grade_model';

        // Find the template definition from fixtures (single variable only).
        $templatesGrouped = SettingsValueTemplateFixtures::getTemplatesGrouped();

        $template = null;
        foreach ($templatesGrouped as $category => $list) {
            foreach ($list as $tpl) {
                if (!\is_array($tpl)) {
                    continue;
                }

                if (($tpl['variable'] ?? null) === $targetVariable) {
                    $template = $tpl;

                    break 2;
                }
            }
        }

        if (null === $template) {
            $this->write("Template not found in SettingsValueTemplateFixtures for variable '{$targetVariable}'. No changes applied.");

            return;
        }

        $jsonExampleRaw = $template['json_example'] ?? null;
        if (!\is_array($jsonExampleRaw) && !\is_string($jsonExampleRaw)) {
            $this->write("Template '{$targetVariable}' has no usable json_example payload. No changes applied.");

            return;
        }

        // Store JSON example as a string in DB.
        $jsonExample = \is_string($jsonExampleRaw)
            ? $jsonExampleRaw
            : (string) json_encode($jsonExampleRaw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Upsert into settings_value_template (no deletes).
        $templateId = $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            [$targetVariable]
        );

        if ($templateId) {
            $updated = $this->connection->executeStatement(
                'UPDATE settings_value_template
                 SET json_example = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?',
                [$jsonExample, (int) $templateId]
            );

            $this->write("Updated settings_value_template for '{$targetVariable}' (rows: {$updated}).");
        } else {
            $inserted = $this->connection->executeStatement(
                'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at)
                 VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                [$targetVariable, $jsonExample]
            );

            $templateId = $this->connection->fetchOne(
                'SELECT id FROM settings_value_template WHERE variable = ?',
                [$targetVariable]
            );

            $this->write("Inserted settings_value_template for '{$targetVariable}' (rows: {$inserted}, id: ".((string) $templateId).').');
        }

        if (!$templateId) {
            $this->write("Unable to resolve template id for '{$targetVariable}'. Settings will not be linked.");

            return;
        }

        // Link settings rows to this template (no value changes, no deletes).
        // Apply to all access URLs to avoid inconsistencies in multi-url setups.
        $linkedRows = $this->connection->executeStatement(
            'UPDATE settings
             SET value_template_id = ?
             WHERE variable = ?
               AND (subkey IS NULL OR subkey = \'\')',
            [(int) $templateId, $targetVariable]
        );

        $this->write("Linked settings.value_template_id for '{$targetVariable}' (rows: {$linkedRows}).");
    }

    public function down(Schema $schema): void
    {
        // Intentionally no-op to avoid deleting or reverting data.
        $this->write('No-op down(): this migration only updates/link templates and does not remove data.');
    }
}
