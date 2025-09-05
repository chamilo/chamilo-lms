<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250905112000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update platform setting title/comment for disable_gdpr.';
    }

    public function up(Schema $schema): void
    {
        $settings = [
            [
                'name' => 'disable_gdpr',
                'title' => 'Disable GDPR features',
                'comment' => 'If you already manage your personal data protection declaration to users elsewhere, you can safely disable this feature.',
            ],
        ];

        foreach ($settings as $setting) {
            $variable = addslashes($setting['name']);
            $title    = addslashes($setting['title']);
            $comment  = addslashes($setting['comment']);

            // Check if the setting exists for the main access_url (1) and no subkey
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
                // Update only title/comment/category; keep selected_value as is
                $this->addSql(\sprintf(
                    "UPDATE settings
                     SET title = '%s',
                         comment = '%s',
                         category = 'privacy'
                     WHERE variable = '%s'
                       AND subkey IS NULL
                       AND access_url = 1",
                    $title,
                    $comment,
                    $variable
                ));
                $this->write(\sprintf('Updated setting: %s', $setting['name']));
            } else {
                // Insert with sensible defaults (selected_value = 'false')
                $this->addSql(\sprintf(
                    "INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                     VALUES
                        ('%s', NULL, NULL, 'privacy', 'false', '%s', '%s', 1, 0, 1)",
                    $variable,
                    $title,
                    $comment
                ));
                $this->write(\sprintf('Inserted setting: %s', $setting['name']));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $variables = ['disable_gdpr'];

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
