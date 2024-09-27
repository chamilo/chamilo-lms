<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240927141000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add notify_boss field to session table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE session
            ADD notify_boss TINYINT(1) DEFAULT 0 NOT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE session
            DROP COLUMN notify_boss
        ");
    }
}
