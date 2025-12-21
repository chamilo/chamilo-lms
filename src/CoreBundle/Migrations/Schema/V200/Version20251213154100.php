<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251213154100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Set settings.access_url_locked to 0 for all rows (MultiURL default unlock).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE settings SET access_url_locked = 0 WHERE access_url_locked IS NULL OR access_url_locked = 1');
    }

    public function down(Schema $schema): void
    {
        // Revert to previous "locked everywhere" behavior (not recommended, but reversible).
        $this->addSql('UPDATE settings SET access_url_locked = 1 WHERE access_url_locked = 0');
    }
}
