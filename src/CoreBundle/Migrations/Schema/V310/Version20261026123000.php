<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261026123000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'automatic_update_checks';
    private const string TITLE = 'Automatic update checks';
    private const string COMMENT = 'Allow the administration news block to check the official updater manifest for available releases. This does not register the portal or send installation statistics.';

    public function getDescription(): string
    {
        return 'Add an opt-in setting for automatic update notices, independent of portal registration.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        // Do not overwrite an administrator's existing opt-in value. An
        // existing installation may already have created this setting via
        // the settings form before this migration is executed.
        $this->addSql(
            'INSERT INTO settings ('
            .'access_url, variable, category, selected_value, title, comment, access_url_changeable, access_url_locked'
            .') SELECT 1, ?, ?, ?, ?, ?, 1, 0 '
            .'WHERE NOT EXISTS ('
            .'SELECT 1 FROM settings WHERE access_url = 1 AND category = ? AND variable = ?'
            .')',
            [self::VARIABLE, 'admin', 'false', self::TITLE, self::COMMENT, 'admin', self::VARIABLE],
        );

        // Metadata must also be correct when the row was previously created
        // on first save with a fallback title equal to the setting key.
        $this->addSql(
            'UPDATE settings SET title = ?, comment = ? '
            .'WHERE access_url = 1 AND category = ? AND variable = ?',
            [self::TITLE, self::COMMENT, 'admin', self::VARIABLE],
        );
    }

    public function down(Schema $schema): void
    {
        // Keep the saved preference on rollback rather than delete a setting
        // that an administrator may already have explicitly enabled.
    }
}
