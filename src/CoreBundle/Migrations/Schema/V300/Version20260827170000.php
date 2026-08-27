<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260827170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Set default value for editor.enabled_support_svg';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE settings SET selected_value = 'false' WHERE variable = 'enabled_support_svg' AND category = 'editor' AND selected_value = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE settings SET selected_value = '' WHERE variable = 'enabled_support_svg' AND category = 'editor' AND selected_value <> ''");
    }
}
