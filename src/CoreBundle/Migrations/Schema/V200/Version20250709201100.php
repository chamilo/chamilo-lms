<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250709201100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Insert or update the platform setting for hosting_limit_identical_email.';
    }

    public function up(Schema $schema): void
    {
        $setting = [
            'variable' => 'hosting_limit_identical_email',
            'selected_value' => '0',
            'title' => 'Limit identical email usage',
            'comment' => 'Maximum number of accounts allowed to share the same e-mail address. Set to 0 to disable this limit.',
            'category' => 'platform',
        ];

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

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM settings
             WHERE variable = 'hosting_limit_identical_email'
               AND subkey IS NULL
               AND access_url = 1
        ");

        $this->write("Removed setting: hosting_limit_identical_email.");
    }
}
