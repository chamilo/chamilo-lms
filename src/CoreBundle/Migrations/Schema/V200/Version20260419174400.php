<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260419174400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove obsolete setting hide_global_announcements_when_not_connected.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM settings
            WHERE variable = 'hide_global_announcements_when_not_connected'
              AND category = 'announcement'
        ");
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        // Recreating deleted settings rows safely requires original metadata defaults.
    }
}
