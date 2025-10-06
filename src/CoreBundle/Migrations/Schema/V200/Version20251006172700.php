<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251006172700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Add survey setting: show_pending_survey_in_menu=false";
    }

    public function up(Schema $schema): void
    {
        $variable = 'show_pending_survey_in_menu';
        $title = addslashes('Show "Pending surveys" in menu');
        $comment = addslashes('Display a menu item that lets users access their pending surveys.');
        $category = 'survey';
        $default = 'false';

        $sqlCheck = "SELECT COUNT(*) AS c FROM settings WHERE variable = '$variable'";
        $result = $this->connection->executeQuery($sqlCheck)->fetchAssociative();

        if ($result && (int)$result['c'] > 0) {
            $this->addSql("
                UPDATE settings
                SET title = '$title',
                    comment = '$comment',
                    category = '$category',
                    type = COALESCE(type, 'radio'),
                    selected_value = COALESCE(selected_value, '$default')
                WHERE variable = '$variable'
            ");
            $this->write("Updated setting: $variable");
        } else {
            $this->addSql("
                INSERT INTO settings
                    (variable, subkey, type, category, selected_value, title, comment, access_url_changeable, access_url_locked, access_url)
                VALUES
                    ('$variable', NULL, 'radio', '$category', '$default', '$title', '$comment', 1, 0, 1)
            ");
            $this->write("Inserted setting: $variable ($default)");
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM settings
            WHERE variable = 'show_pending_survey_in_menu'
              AND subkey IS NULL
              AND access_url = 1
        ");
        $this->write("Removed setting: show_pending_survey_in_menu");
    }
}
