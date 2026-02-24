<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260222133800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fixes the setting "platform.timepicker_increment" by setting it to NULL if it was set to "false".';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE settings SET selected_value = NULL WHERE selected_value = 'false' AND variable = 'timepicker_increment' AND category = 'platform'");
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty (non-destructive migration).
    }
}
