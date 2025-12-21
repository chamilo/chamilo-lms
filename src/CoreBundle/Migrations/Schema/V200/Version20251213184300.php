<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251213184300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "Remove deprecated 'icons_mode_svg' setting from settings.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable = 'icons_mode_svg'");
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty: the setting is removed from code/schema.
    }
}
