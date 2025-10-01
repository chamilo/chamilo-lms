<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250924220200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Ensure default platform settings for global chat: allow_global_chat=false, hide_chat_video=true.';
    }

    public function up(Schema $schema): void
    {
        $settings = [
            [
                'name' => 'allow_global_chat',
                'title' => 'Allow global chat',
                'comment' => 'Users can chat with each other',
                'category' => 'chat',
                'default' => 'false',
            ],
            [
                'name' => 'hide_chat_video',
                'title' => 'Hide videochat option in global chat',
                'comment' => '',
                'category' => 'chat',
                'default' => 'true',
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
                 WHERE variable = '%s'",
                $variable
            );

            $stmt = $this->connection->executeQuery($sqlCheck);
            $result = $stmt->fetchAssociative();

            if ($result && (int) $result['count'] > 0) {
                $this->addSql(\sprintf(
                    "UPDATE settings
                     SET title = '%s',
                         comment = '%s',
                         category = '%s',
                         type = COALESCE(type, 'radio'),
                         selected_value = '%s'
                     WHERE variable = '%s'",
                    $title,
                    $comment,
                    $category,
                    $default,
                    $variable
                ));
                $this->write(\sprintf('Updated setting: %s (selected_value set to %s)', $setting['name'], $setting['default']));
            } else {
                $this->addSql(\sprintf(
                    "INSERT INTO settings
                        (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                     VALUES
                        ('%s', NULL, 'radio', '%s', '%s', '%s', '%s', 1, 0, 1)",
                    $variable,
                    $category,
                    $default,
                    $title,
                    $comment
                ));
                $this->write(\sprintf('Inserted setting: %s (%s)', $setting['name'], $setting['default']));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $variables = ['allow_global_chat', 'hide_chat_video'];

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
