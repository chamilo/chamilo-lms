<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250709010000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix bad timezone in users added in alpha stage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE user
            SET timezone = 'Europe/Paris'
            WHERE username in ('anon', 'fallback_user')
        ");
        $this->write('Fixed bad timezone introduced in alpha <3 for some default users.');
    }

    public function down(Schema $schema): void
    {
        // Nothing to do
    }
}
