<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\DataFixtures\SettingsCurrentFixtures;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Version20230216122900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate configuration values to settings';
    }

    public function up(Schema $schema): void
    {
        $configurationValues = SettingsCurrentFixtures::getNewConfigurationSettings();

        foreach ($configurationValues as $category => $settings) {
            foreach ($settings as $setting) {
                $variable = $setting['name'];
                $category = strtolower($category);
                $result = $this->connection
                    ->executeQuery(
                        "SELECT COUNT(1) FROM settings WHERE variable = '$variable' AND category = '{$category}'"
                    )
                ;
                $count = $result->fetchNumeric()[0];
                $selectedValue = $this->getConfigurationSelectedValue($variable);
                error_log('Migration: Setting variable '.$variable.' category '.$category.' value '.$selectedValue);

                // To use by default courses page if this option is not empty.
                if ('redirect_index_to_url_for_logged_users' === $variable && !empty($selectedValue)) {
                    $selectedValue = 'courses';
                }
                if (empty($count)) {
                    $this->addSql(
                        "INSERT INTO settings (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, '{$variable}', '{$category}', '{$selectedValue}', '{$variable}', 1, 1)"
                    );
                } else {
                    $this->addSql(
                        "UPDATE settings SET selected_value = '{$selectedValue}', category = '{$category}' WHERE variable = '$variable' AND category = '{$category}'"
                    );
                }
            }
        }

        // Rename setting for hierarchical skill presentation.
        $this->addSql(
            "UPDATE settings SET variable = 'skills_hierarchical_view_in_user_tracking', title = 'skills_hierarchical_view_in_user_tracking' WHERE variable = 'table_of_hierarchical_skill_presentation'"
        );

        // Insert extra fields required.
        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'session_courses_read_only_mode' AND item_type = 2 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (2, 13, 'session_courses_read_only_mode', 'Lock Course In Session', 1, 1, 1, NOW())"
            );
        }

        // Insert extra fields required.
        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'is_mandatory' AND item_type = 12 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (12, 13, 'is_mandatory', 'IsMandatory', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'show_in_catalogue' AND item_type = 2 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (2, 3, 'show_in_catalogue', 'Show in catalogue', 1, 1, 0, NOW())"
            );
            $this->addSql(
                'SET @ef_id = LAST_INSERT_ID()'
            );
            $this->addSql(
                "INSERT INTO extra_field_options (field_id, option_value, display_text, priority, priority_message, option_order) VALUES (@ef_id, '1', 'Yes', NULL, NULL, 1), (@ef_id, '0', 'No', NULL, NULL, 2)"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'multiple_language' AND item_type = 2 AND value_type = 5"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (2, 5, 'multiple_language', 'Multiple Language', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'send_notification_at_a_specific_date' AND item_type = 21 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (21, 13, 'send_notification_at_a_specific_date', 'Send notification at a specific date', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'date_to_send_notification' AND item_type = 21 AND value_type = 6"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (21, 6, 'date_to_send_notification', 'Date to send notification', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'send_to_users_in_session' AND item_type = 21 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (21, 13, 'send_to_users_in_session', 'Send to users in session', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'tags' AND item_type = 22 AND value_type = 10"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (22, 10, 'tags', 'Tags', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'acquisition' AND item_type = 20 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (20, 3, 'acquisition', 'Acquisition', 1, 1, 0, NOW())"
            );
            $this->addSql(
                'SET @ef_id = LAST_INSERT_ID()'
            );
            $this->addSql(
                "INSERT INTO extra_field_options (field_id, option_value, display_text, priority, priority_message, option_order) VALUES (@ef_id, '1', 'Acquired', NULL, NULL, 1), (@ef_id, '2', 'In the process of acquisition', NULL, NULL, 2), (@ef_id, '3', 'Not acquired', NULL, NULL, 3)"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'invisible' AND item_type = 20 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (20, 13, 'invisible', 'Invisible', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'start_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (7, 7, 'start_date', 'StartDate', 1, 1, 1, NOW())"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'end_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (7, 7, 'end_date', 'EndDate', 1, 1, 1, NOW())"
            );
        }

        $attachmentExists = $this->connection->fetchOne("SELECT COUNT(*) FROM extra_field WHERE variable = 'attachment' AND item_type = 13");
        if (0 == $attachmentExists) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (13, 18, 'attachment', 'Attachment', 1, 1, 1, NOW())"
            );
        }

        $sendToCoachesExists = $this->connection->fetchOne("SELECT COUNT(*) FROM extra_field WHERE variable = 'send_to_coaches' AND item_type = 13");
        if (0 == $sendToCoachesExists) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (13, 13, 'send_to_coaches', 'Send to Coaches', 1, 1, 1, NOW())"
            );
        }

        $workTimeExists = $this->connection->fetchOne("SELECT COUNT(*) FROM extra_field WHERE variable = 'work_time' AND item_type = 9");
        if (0 == $workTimeExists) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (9, 15, 'work_time', 'Considered working time', 1, 1, 1, NOW())"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $configurationValues = SettingsCurrentFixtures::getNewConfigurationSettings();

        foreach ($configurationValues as $category => $settings) {
            foreach ($settings as $setting) {
                $variable = $setting['name'];
                $category = strtolower($category);
                $result = $this->connection
                    ->executeQuery(
                        "SELECT COUNT(1) FROM settings WHERE variable = '$variable' AND category = '$category'"
                    )
                ;
                $count = $result->fetchNumeric()[0];
                if (!empty($count)) {
                    $this->addSql(
                        "DELETE FROM settings WHERE variable = '{$variable}' AND category = '$category'"
                    );
                }
            }
        }

        // Delete extra fields required.
        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'end_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'end_date' AND item_type = 7 AND value_type = 7"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'start_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'start_date' AND item_type = 7 AND value_type = 7"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'invisible' AND item_type = 20 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'invisible' AND item_type = 20 AND value_type = 13"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'acquisition' AND item_type = 20 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'acquisition' AND item_type = 20 AND value_type = 3"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'tags' AND item_type = 22 AND value_type = 10"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'tags' AND item_type = 22 AND value_type = 10"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'multiple_language' AND item_type = 2 AND value_type = 5"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'multiple_language' AND item_type = 2 AND value_type = 5"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'show_in_catalogue' AND item_type = 2 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'show_in_catalogue' AND item_type = 2 AND value_type = 3"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'session_courses_read_only_mode' AND item_type = 2 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'session_courses_read_only_mode' AND item_type = 2 AND value_type = 13"
            );
        }

        $result = $this->connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'is_mandatory' AND item_type = 12 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'is_mandatory' AND item_type = 12 AND value_type = 13"
            );
        }
    }

    public function getConfigurationSelectedValue(string $variable): string
    {
        global $_configuration;
        $kernel = $this->container->get('kernel');
        $oldConfigPath = $this->getUpdateRootPath().'/app/config/configuration.php';
        $configFileLoaded = \in_array($oldConfigPath, get_included_files(), true);
        if (!$configFileLoaded) {
            include_once $oldConfigPath;
        }

        $selectedValue = '';
        $settingValue = $this->getConfigurationValue($variable, $_configuration);
        if (\is_array($settingValue)) {
            $selectedValue = json_encode($settingValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (\is_bool($settingValue)) {
            $selectedValue = var_export($settingValue, true);
        } else {
            $selectedValue = (string) $settingValue;
        }

        return $selectedValue;
    }
}
