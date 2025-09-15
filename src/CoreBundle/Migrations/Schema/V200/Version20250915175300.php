<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250915175300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return "TicketStatus: rename title 'Close' → 'Closed' (state, not action). Data backfill only.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE ticket_status SET title = 'Closed' WHERE LOWER(title) = 'close'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE ticket_status SET title = 'Close' WHERE LOWER(title) = 'closed'");
    }
}
