<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Make the current SettingsCurrent mapping usable during older ORM-based migrations.
 */
final class Version20240112103359 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add confidential setting metadata before ORM-based settings migrations.';
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

    public function down(Schema $schema): void
    {
        // Historical compatibility metadata is intentionally retained.
    }
}
