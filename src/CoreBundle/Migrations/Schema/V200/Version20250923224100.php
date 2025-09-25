<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250923224100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update platform setting title/comment for security 2FA global toggle (2fa_enable).';
    }

    public function up(Schema $schema): void
    {
        $settings = [
            [
                'name' => '2fa_enable',
                'title' => 'Enable 2FA',
                'comment' => "Add fields in the password update page to enable 2FA using a TOTP authenticator app. When disabled globally, users won't see 2FA fields and won't be prompted for 2FA at login, even if they had enabled it previously.",
                'category' => 'security',
                'default' => 'false',
            ],
        ];

        foreach ($settings as $setting) {
            $variable = addslashes($setting['name']);
            $title = addslashes($setting['title']);
            $comment = addslashes($setting['comment']);
            $category = addslashes($setting['category']);
            $default = addslashes($setting['default']);

            $sqlCheck = \sprintf(
                "SELECT COUNT(*) AS count
                 FROM settings
                 WHERE variable = '%s'
                   AND subkey IS NULL
                   AND access_url = 1",
                $variable
            );

            $stmt = $this->connection->executeQuery($sqlCheck);
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
    }

    public function down(Schema $schema): void
    {
        $variables = ['2fa_enable'];

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
