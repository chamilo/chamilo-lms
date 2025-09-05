<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class Version20250905112000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update platform settings title/comment for disable_gdpr and show_conditions_to_user, and link show_conditions_to_user to its value template.';
    }

    public function up(Schema $schema): void
    {
        // Upsert settings (title/comment/category and default selected_value if missing)
        $settings = [
            [
                'name' => 'disable_gdpr',
                'title' => 'Disable GDPR features',
                'comment' => 'If you already manage your personal data protection declaration to users elsewhere, you can safely disable this feature.',
                'category' => 'profile',
                'default' => 'false',
            ],
            [
                'name' => 'show_conditions_to_user',
                'title' => 'Show specific registration conditions',
                'comment' => "Show multiple conditions to user during sign up process. Provide an array with each element containing 'variable' (internal extra field name), 'display_text' (simple text for a checkbox), 'text_area' (long text of conditions).",
                'category' => 'registration',
                'default' => '[]',
            ],
        ];

        foreach ($settings as $setting) {
            $variable = addslashes($setting['name']);
            $title    = addslashes($setting['title']);
            $comment  = addslashes($setting['comment']);
            $category = addslashes($setting['category']);
            $default  = addslashes($setting['default']);

            $sqlCheck = \sprintf(
                "SELECT COUNT(*) AS count
                 FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                $variable
            );

            $stmt   = $this->connection->executeQuery($sqlCheck);
            $result = $stmt->fetchAssociative();

            if ($result && (int) $result['count'] > 0) {
                $this->addSql(\sprintf(
                    "UPDATE settings
                     SET title = '%s',
                         comment = '%s',
                         category = '%s'
                     WHERE variable = '%s'
                       AND subkey IS NULL
                       AND access_url = 1",
                    $title,
                    $comment,
                    $category,
                    $variable
                ));
                $this->write(\sprintf('Updated setting: %s', $setting['name']));
            } else {
                $this->addSql(\sprintf(
                    "INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                     VALUES
                        ('%s', NULL, NULL, '%s', '%s', '%s', '%s', 1, 0, 1)",
                    $variable,
                    $category,
                    $default,
                    $title,
                    $comment
                ));
                $this->write(\sprintf('Inserted setting: %s', $setting['name']));
            }
        }

        // Link show_conditions_to_user to its value template (from fixtures), only this variable
        $targetVariable = 'show_conditions_to_user';
        $templatesGrouped = SettingsValueTemplateFixtures::getTemplatesGrouped();

        foreach ($templatesGrouped as $category => $settingsList) {
            foreach ($settingsList as $tpl) {
                if (!isset($tpl['variable']) || $tpl['variable'] !== $targetVariable) {
                    continue;
                }

                $jsonExample = json_encode(
                    $tpl['json_example'],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );

                // Check if template already exists
                $templateId = $this->connection->fetchOne(
                    'SELECT id FROM settings_value_template WHERE variable = ?',
                    [$targetVariable]
                );

                if ($templateId) {
                    $this->connection->executeStatement(
                        'UPDATE settings_value_template SET json_example = ?, updated_at = NOW() WHERE id = ?',
                        [$jsonExample, $templateId]
                    );
                } else {
                    $this->connection->executeStatement(
                        'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at)
                         VALUES (?, ?, NOW(), NOW())',
                        [$targetVariable, $jsonExample]
                    );
                    $templateId = $this->connection->lastInsertId();
                }

                if ($templateId) {
                    $updatedRows = $this->connection->executeStatement(
                        'UPDATE settings
                         SET value_template_id = ?
                         WHERE variable = ? AND subkey IS NULL AND access_url = 1',
                        [$templateId, $targetVariable]
                    );
                    $this->write("Linked settings.value_template_id for '{$targetVariable}' (updated rows: {$updatedRows})");
                }

                // We found and processed the target; break both loops
                break 2;
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Unlink and remove the value template for show_conditions_to_user (if present)
        $targetVariable = 'show_conditions_to_user';

        $templateId = $this->connection->fetchOne(
            'SELECT id FROM settings_value_template WHERE variable = ?',
            [$targetVariable]
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

            $this->write("Deleted template with ID {$templateId} for variable {$targetVariable}");
        }

        // Remove both settings entries (as in previous pattern)
        $variables = ['disable_gdpr', 'show_conditions_to_user'];

        foreach ($variables as $variable) {
            $this->addSql(\sprintf(
                "DELETE FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                addslashes($variable)
            ));
            $this->write(\sprintf('Removed setting: %s.', $variable));
        }
    }
}
