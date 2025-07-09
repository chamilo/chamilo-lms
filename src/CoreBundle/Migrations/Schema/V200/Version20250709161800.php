<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250709161800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update admin settings for Chamilo latest news and support block.';
    }

    public function up(Schema $schema): void
    {
        $settings = [
            [
                'variable' => 'chamilo_latest_news',
                'selected_value' => 'true',
                'title' => 'Latest news',
                'comment' => 'Get the latest news from Chamilo, including security vulnerabilities and events, directly inside your administration panel. These pieces of news will be checked on the Chamilo news server every time you load the administration page and are only visible to administrators.',
                'category' => 'admin',
            ],
            [
                'variable' => 'chamilo_support',
                'selected_value' => 'true',
                'title' => 'Chamilo support block',
                'comment' => 'Get pro tips and an easy way to contact official service providers for professional support, directly from the makers of Chamilo. This block appears on your administration page, is only visible by administrators, and refreshes every time you load the administration page.',
                'category' => 'admin',
            ],
        ];

        foreach ($settings as $setting) {
            // Check if the setting exists for access_url = 1
            $sqlCheck = sprintf(
                "SELECT COUNT(*) as count
                 FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                addslashes($setting['variable'])
            );

            $stmt = $this->connection->executeQuery($sqlCheck);
            $result = $stmt->fetchAssociative();

            if ($result && (int)$result['count'] > 0) {
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
                    addslashes($setting['variable'])
                ));
                $this->write(sprintf("Updated setting: %s", $setting['variable']));
            } else {
                // INSERT new setting
                $this->addSql(sprintf(
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
                $this->write(sprintf("Inserted setting: %s", $setting['variable']));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM settings
             WHERE variable IN ('chamilo_latest_news', 'chamilo_support')
               AND subkey IS NULL
               AND access_url = 1
        ");

        $this->write("Removed chamilo_latest_news and chamilo_support settings.");
    }
}
