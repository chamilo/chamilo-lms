<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsValueTemplateFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250724180700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update catalog settings and synchronize their JSON templates.';
    }

    public function up(Schema $schema): void
    {
        // 1) Insert or update catalog settings
        $settings = [
            [
                'variable'       => 'course_catalog_settings',
                'selected_value' => '',
                'title'          => 'Course Catalog Settings',
                'comment'        => 'JSON configuration for course catalog: link settings, filters, sort options, and more.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'session_catalog_settings',
                'selected_value' => '',
                'title'          => 'Session Catalog Settings',
                'comment'        => 'JSON configuration for session catalog: filters and display options.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'show_courses_descriptions_in_catalog',
                'selected_value' => 'false',
                'title'          => 'Show Course Descriptions',
                'comment'        => 'Display course descriptions within the catalog listing.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'course_catalog_published',
                'selected_value' => 'false',
                'title'          => 'Published Courses Only',
                'comment'        => 'Limit the catalog to only courses marked as published.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'course_catalog_display_in_home',
                'selected_value' => 'true',
                'title'          => 'Display Catalog on Homepage',
                'comment'        => 'Show the course catalog block on the platform homepage.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'hide_public_link',
                'selected_value' => 'false',
                'title'          => 'Hide Public Link',
                'comment'        => 'Remove the public URL link from course cards.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'only_show_selected_courses',
                'selected_value' => 'false',
                'title'          => 'Only Selected Courses',
                'comment'        => 'Show only manually selected courses in the catalog.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'only_show_course_from_selected_category',
                'selected_value' => 'false',
                'title'          => 'Only Selected Category',
                'comment'        => 'Show only courses from a specific selected category.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'allow_students_to_browse_courses',
                'selected_value' => 'true',
                'title'          => 'Allow Student Browsing',
                'comment'        => 'Permit students to browse and filter the course catalog.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'course_catalog_hide_private',
                'selected_value' => 'true',
                'title'          => 'Hide Private Courses',
                'comment'        => 'Exclude private courses from the catalog display.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'show_courses_sessions',
                'selected_value' => 'true',
                'title'          => 'Show Courses & Sessions',
                'comment'        => 'Include both courses and sessions in catalog results.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'allow_session_auto_subscription',
                'selected_value' => 'false',
                'title'          => 'Auto Session Subscription',
                'comment'        => 'Enable automatic subscription to sessions for users.',
                'category'       => 'catalog',
            ],
            [
                'variable'       => 'course_subscription_in_user_s_session',
                'selected_value' => 'false',
                'title'          => 'Subscription in Session View',
                'comment'        => 'Allow users to subscribe to courses directly from their session page.',
                'category'       => 'catalog',
            ],
        ];

        foreach ($settings as $setting) {
            $variable = addslashes($setting['variable']);
            $count = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM settings
                   WHERE variable = '$variable'
                     AND subkey IS NULL
                     AND access_url = 1"
            );

            if ($count > 0) {
                // UPDATE existing setting
                $this->addSql(sprintf(
                    "UPDATE settings
                        SET selected_value = '%s',
                            title = '%s',
                            comment = '%s',
                            category = '%s'
                      WHERE variable = '%s'
                        AND subkey IS NULL
                        AND access_url = 1",
                    addslashes($setting['selected_value']),
                    addslashes($setting['title']),
                    addslashes($setting['comment']),
                    addslashes($setting['category']),
                    $variable
                ));
                $this->write("Updated setting: {$variable}");
            } else {
                // INSERT new setting
                $this->addSql(sprintf(
                    "INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment,
                         access_url_changeable, access_url_locked, access_url)
                     VALUES
                        ('%s', NULL, NULL, '%s', '%s', '%s', '%s', 1, 0, 1)",
                    $variable,
                    addslashes($setting['category']),
                    addslashes($setting['selected_value']),
                    addslashes($setting['title']),
                    addslashes($setting['comment'])
                ));
                $this->write("Inserted setting: {$variable}");
            }
        }

        // 2) Synchronize JSON templates for catalog settings
        $groupedTemplates = SettingsValueTemplateFixtures::getTemplatesGrouped();
        $catalogTemplates = $groupedTemplates['catalog'] ?? [];

        foreach ($catalogTemplates as $templateData) {
            $fullVariable = $templateData['variable'];  // e.g. "catalog.course_catalog_settings"
            $jsonExample  = json_encode(
                $templateData['json_example'],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            // Create or update the template record
            $templateId = $this->connection->fetchOne(
                'SELECT id FROM settings_value_template WHERE variable = ?',
                [$fullVariable]
            );

            if ($templateId) {
                $this->connection->executeStatement(
                    'UPDATE settings_value_template
                        SET json_example = ?, updated_at = NOW()
                      WHERE id = ?',
                    [$jsonExample, $templateId]
                );
                $this->write("Updated JSON template for '{$fullVariable}'.");
            } else {
                $this->connection->executeStatement(
                    'INSERT INTO settings_value_template
                        (variable, json_example, created_at, updated_at)
                     VALUES (?, ?, NOW(), NOW())',
                    [$fullVariable, $jsonExample]
                );
                $templateId = (int)$this->connection->lastInsertId();
                $this->write("Inserted JSON template for '{$fullVariable}'.");
            }

            // Strip the "catalog." prefix** to match the settings.variable column
            $strippedVar = preg_replace('/^catalog\./', '', $fullVariable);

            // Link the template to the matching settings rows
            $linkedRows = $this->connection->executeStatement(
                "UPDATE settings
                    SET value_template_id = ?
                  WHERE variable = ?
                    AND subkey IS NULL
                    AND access_url = 1",
                [$templateId, $strippedVar]
            );
            $this->write("Linked {$linkedRows} settings rows to template '{$fullVariable}'.");
        }
    }

    public function down(Schema $schema): void
    {
        // Remove all catalog settings
        $this->addSql("
            DELETE FROM settings
             WHERE variable IN (
               'course_catalog_settings',
               'session_catalog_settings',
               'show_courses_descriptions_in_catalog',
               'course_catalog_published',
               'course_catalog_display_in_home',
               'hide_public_link',
               'only_show_selected_courses',
               'only_show_course_from_selected_category',
               'allow_students_to_browse_courses',
               'course_catalog_hide_private',
               'show_courses_sessions',
               'allow_session_auto_subscription',
               'course_subscription_in_user_s_session'
             )
               AND subkey IS NULL
               AND access_url = 1
        ");

        $this->write('Removed all catalog.* settings.');
    }
}
