<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250714184000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add setting registration.hide_legal_accept_checkbox';
    }

    public function up(Schema $schema): void
    {
        $variable = 'hide_legal_accept_checkbox';
        $category = 'registration';
        $selectedValue = 'false';
        $title = 'Hide legal accept checkbox in Terms and Conditions page';
        $comment = 'If set to true, removes the "I have read and accept" checkbox in the Terms and Conditions page flow.';

        $sqlCheck = \sprintf(
            "SELECT COUNT(*) as count
             FROM settings
             WHERE variable = '%s'
               AND subkey IS NULL
               AND access_url = 1",
            addslashes($variable)
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
                addslashes($selectedValue),
                addslashes($title),
                addslashes($comment),
                addslashes($category),
                addslashes($variable)
            ));
            $this->write(\sprintf('Updated setting: %s', $variable));
        } else {
            $this->addSql(\sprintf(
                "INSERT INTO settings
                    (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                 VALUES
                    ('%s', NULL, NULL, '%s', '%s', '%s', '%s', 1, 0, 1)",
                addslashes($variable),
                addslashes($category),
                addslashes($selectedValue),
                addslashes($title),
                addslashes($comment)
            ));
            $this->write(\sprintf('Inserted setting: %s', $variable));
        }
    }

    public function down(Schema $schema): void
    {
        $variable = 'hide_legal_accept_checkbox';

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
