<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260915000000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'chamilo_database_version';

    public function getDescription(): string
    {
        return 'Remove the chamilo_database_version setting, a hand-written literal no code reads any more';
    }

    public function up(Schema $schema): void
    {
        // Removing it from the settings schema does not delete the row an existing
        // platform already holds. Without this the row survives with no form, no
        // category and access_url_locked = 1, set by V200\Version20251215074200.
        if ($schema->hasTable('settings')) {
            $this->addSql('DELETE FROM settings WHERE variable = ?', [self::VARIABLE]);
        }
    }

    /**
     * Recreates the row with the value the schema used to declare. That value was never
     * maintained: migrations never raised it, which is why the setting was removed.
     */
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $this->addSql(
            "INSERT INTO settings (variable, subkey, type, category, selected_value, title, comment, access_url, access_url_changeable, access_url_locked)
             SELECT ?, NULL, 'textfield', 'Platform', '2.0.0', 'Current version of the database schema used by Chamilo', 'Displays the current DB version to match the Chamilo core version.', 1, 0, 1
             FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM settings WHERE variable = ?)",
            [self::VARIABLE, self::VARIABLE]
        );
    }
}
