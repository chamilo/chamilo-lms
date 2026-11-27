<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Any migration that reads a setting through the ORM (SettingsManager::getSetting(), api_get_setting(), or a
 * service or listener that does) loads SettingsCurrent, whose mapping includes confidential:
 * V310\Version20261113193000 only adds it for real. Version20240112103359 adds it early too, but it runs after
 * every V200 migration from 2017 to 2023, and a migration from 1.11 failed on "t0.confidential" in
 * Version20201212203625. This one runs right after Version20, which renames settings_current to settings.
 * Same definition and guard, so that the later migrations then leave the column as it is.
 */
final class Version20170523105900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the confidential setting column that the V200 ORM reads of settings need';
    }

    public function up(Schema $schema): void
    {
        // Both names, like Version20240112103359: Version20 normally renamed settings_current already.
        foreach (['settings', 'settings_current'] as $tableName) {
            if ($schema->hasTable($tableName) && !$schema->getTable($tableName)->hasColumn('confidential')) {
                $this->addSql('ALTER TABLE '.$tableName.' ADD confidential TINYINT(1) NOT NULL DEFAULT 0');
            }
        }
    }

    public function down(Schema $schema): void {}
}
