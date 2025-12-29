<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251229112200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add chat setting to control mirroring private conversations into documents.';
    }

    public function up(Schema $schema): void
    {
        $setting = [
            'variable' => 'save_private_conversations_in_documents',
            'selected_value' => 'false',
            'title' => 'Save private conversations in documents',
            'comment' => 'If enabled, 1:1 private chat messages will be mirrored in the course chat history documents. Recommended to keep disabled for privacy.',
            'category' => 'chat',
        ];

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
        $exists = $result && (int) ($result['count'] ?? 0) > 0;

        if ($exists) {
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

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM settings
             WHERE variable = 'save_private_conversations_in_documents'
               AND subkey IS NULL
               AND access_url = 1
        ");

        $this->write('Removed setting: save_private_conversations_in_documents');
    }
}
