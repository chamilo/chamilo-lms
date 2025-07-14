<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250711143900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add password_rotation_days setting and user.password_updated_at column; migrate existing extra-field data and remove old extra-field';
    }

    public function up(Schema $schema): void
    {
        // 1. Add new column to user table
        $this->addSql('ALTER TABLE `user` ADD COLUMN `password_updated_at` DATETIME DEFAULT NULL;');

        // 2. Insert or update the new setting in settings table
        $setting = [
            'variable' => 'password_rotation_days',
            'selected_value' => '0',
            'title' => 'Password rotation interval (days)',
            'comment' => 'Number of days before users must rotate their password (0 = disabled).',
            'category' => 'security',
        ];

        $sqlCheck = \sprintf(
            "SELECT COUNT(*) AS count
               FROM settings
              WHERE variable = '%s'
                AND subkey IS NULL
                AND access_url = 1",
            addslashes($setting['variable'])
        );
        $result = $this->connection->fetchAssociative($sqlCheck);

        if ($result && (int) $result['count'] > 0) {
            // UPDATE existing setting
            $this->addSql(\sprintf(
                "UPDATE settings
                    SET selected_value = '%s',
                        title          = '%s',
                        comment        = '%s',
                        category       = '%s'
                  WHERE variable = '%s'
                    AND subkey IS NULL
                    AND access_url = 1",
                addslashes($setting['selected_value']),
                addslashes($setting['title']),
                addslashes($setting['comment']),
                addslashes($setting['category']),
                addslashes($setting['variable'])
            ));
        } else {
            // INSERT new setting
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
        }

        // 3. Migrate existing extra-field data into the new column (if tables exist)
        if ($schema->hasTable('extra_field_values') && $schema->hasTable('extra_field')) {
            // Copy field_value into user.password_updated_at for variable 'password_updated_at'
            $this->addSql("
                UPDATE `user` AS u
                INNER JOIN `extra_field_values` AS efv
                    ON efv.item_id = u.id
                INNER JOIN `extra_field` AS ef
                    ON ef.id = efv.field_id
                   AND ef.variable = 'password_updated_at'
                SET u.password_updated_at = efv.field_value
            ");

            // Delete the old extra_field_values entries
            $this->addSql("
                DELETE efv
                  FROM `extra_field_values` AS efv
                INNER JOIN `extra_field` AS ef
                    ON ef.id = efv.field_id
                   AND ef.variable = 'password_updated_at'
            ");

            // Delete the old extra_field definition
            $this->addSql("
                DELETE ef
                  FROM `extra_field` AS ef
                 WHERE ef.variable = 'password_updated_at'
                   AND ef.item_type = 1
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // 1. Remove the setting
        $this->addSql("
            DELETE FROM settings
             WHERE variable = 'password_rotation_days'
               AND subkey IS NULL
               AND access_url = 1
        ");

        // 2. Drop the column from user table
        $this->addSql('ALTER TABLE `user` DROP COLUMN `password_updated_at`;');
    }
}
