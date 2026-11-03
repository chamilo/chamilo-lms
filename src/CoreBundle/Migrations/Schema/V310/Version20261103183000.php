<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Remove an obsolete converter setting whose implementation is no longer active.
 */
final class Version20261103183000 extends AbstractMigrationChamilo
{
    private const string SETTING_VARIABLE = 'unoconv_binaries';

    public function getDescription(): string
    {
        return 'Remove the unused UNO converter setting from existing installations.';
    }

    public function up(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $table) {
            if (!$schema->hasTable($table) || !$schema->getTable($table)->hasColumn('variable')) {
                continue;
            }

            $this->addSql(
                \sprintf('DELETE FROM %s WHERE variable = ?', $table),
                [self::SETTING_VARIABLE]
            );
        }
    }

    public function down(Schema $schema): void
    {
        // The original per-access-URL values cannot be reconstructed safely.
    }
}
