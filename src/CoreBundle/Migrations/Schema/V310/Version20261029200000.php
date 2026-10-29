<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261029200000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Store the end of repeated calendar events as a BIGINT timestamp, so a series can end after January 2038.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_calendar_event_repeat')) {
            return;
        }

        $this->addSql('ALTER TABLE c_calendar_event_repeat MODIFY cal_end BIGINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_calendar_event_repeat MODIFY cal_end INT DEFAULT NULL');
    }
}
