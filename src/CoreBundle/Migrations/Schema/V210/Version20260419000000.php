<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Any migration that reads a setting loads SettingsCurrent through the ORM, whose mapping includes
 * the confidential column that V310\Version20261113193000 only adds later. V200\Version20240112103359
 * adds it early on an upgrade from 1.11, but a platform that only runs the migrations from V210 on
 * never gets there. Placed before Version20260419110300, the first migration of V210.
 */
final class Version20260419000000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the confidential setting metadata that the V210 ORM reads of settings need';
    }

    public function up(Schema $schema): void
    {
        // Earlier versions may still use the old settings_current table name.
        foreach (['settings', 'settings_current'] as $tableName) {
            if ($schema->hasTable($tableName) && !$schema->getTable($tableName)->hasColumn('confidential')) {
                $this->addSql('ALTER TABLE '.$tableName.' ADD confidential TINYINT(1) NOT NULL DEFAULT 0');
            }
        }
    }

    public function down(Schema $schema): void {}
}
