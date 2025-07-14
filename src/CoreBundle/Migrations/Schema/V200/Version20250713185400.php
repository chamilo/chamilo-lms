<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250713185400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update platform settings for user_session_display_mode and course_sequence_valid_only_in_same_session.';
    }

    public function up(Schema $schema): void
    {
        $settings = [
            [
                'variable' => 'user_session_display_mode',
                'selected_value' => 'card',
                'title' => 'Default display mode for My Sessions page',
                'comment' => 'Defines the default visual style for showing sessions on the My Sessions page. Options: card (visual blocks), list (classic view).',
                'category' => 'session',
            ],
            [
                'variable' => 'course_sequence_valid_only_in_same_session',
                'selected_value' => 'false',
                'title' => 'Course sequence validation only in the same session',
                'comment' => 'If enabled, course dependencies are enforced only within the same session and do not block other sessions or standalone courses.',
                'category' => 'course',
            ],
        ];

        foreach ($settings as $setting) {
            $sqlCheck = \sprintf(
                "SELECT COUNT(*) as count
                 FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                addslashes($setting['variable'])
            );

            $stmt = $this->connection->executeQuery($sqlCheck);
            $result = $stmt->fetchAssociative();

            if ($result && (int) $result['count'] > 0) {
                $this->addSql(\sprintf(
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
                    addslashes($setting['variable'])
                ));
                $this->write(\sprintf('Updated setting: %s', $setting['variable']));
            } else {
                $this->addSql(\sprintf(
                    "INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                     VALUES
                        ('%s', NULL, NULL, '%s', '%s', '%s', '%s', 1, 0, 1)",
                    addslashes($setting['variable']),
                    addslashes($setting['category']),
                    addslashes($setting['selected_value']),
                    addslashes($setting['title']),
                    addslashes($setting['comment'])
                ));
                $this->write(\sprintf('Inserted setting: %s', $setting['variable']));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $variables = [
            'user_session_display_mode',
            'course_sequence_valid_only_in_same_session',
        ];

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
