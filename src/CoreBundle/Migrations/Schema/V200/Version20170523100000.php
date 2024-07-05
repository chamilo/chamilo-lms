<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170523100000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE settings_current TO settings');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE settings TO settings_current');
    }
}
