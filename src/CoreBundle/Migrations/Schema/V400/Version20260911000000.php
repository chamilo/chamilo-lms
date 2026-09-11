<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V400;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260911000000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'chamilo_database_version';

    public function getDescription(): string
    {
        return 'Remove the deprecated chamilo_database_version setting from existing installations.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $this->addSql(
            'DELETE FROM settings WHERE variable = ?',
            [self::VARIABLE],
        );
    }

    public function down(Schema $schema): void
    {
        // Intentional one-way cleanup: the setting is deprecated and its value
        // was never maintained, so there is nothing meaningful to restore.
    }
}
