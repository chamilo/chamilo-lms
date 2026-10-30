<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\DateTimeType;

final class Version20261029200000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Store the end of repeated calendar events as a UTC DATETIME instead of an INT timestamp, which stops in January 2038.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_calendar_event_repeat')
            || $schema->getTable('c_calendar_event_repeat')->getColumn('cal_end')->getType() instanceof DateTimeType
        ) {
            return;
        }

        // DATE_ADD on the epoch reads the timestamp as UTC, whatever the time zone of the connection.
        $this->addSql('ALTER TABLE c_calendar_event_repeat ADD cal_end_datetime DATETIME DEFAULT NULL');
        $this->addSql("UPDATE c_calendar_event_repeat SET cal_end_datetime = DATE_ADD('1970-01-01 00:00:00', INTERVAL cal_end SECOND) WHERE cal_end IS NOT NULL");
        $this->addSql('ALTER TABLE c_calendar_event_repeat DROP cal_end');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_end_datetime cal_end DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_calendar_event_repeat ADD cal_end_timestamp INT DEFAULT NULL');
        $this->addSql("UPDATE c_calendar_event_repeat SET cal_end_timestamp = TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', cal_end) WHERE cal_end IS NOT NULL");
        $this->addSql('ALTER TABLE c_calendar_event_repeat DROP cal_end');
        $this->addSql('ALTER TABLE c_calendar_event_repeat CHANGE cal_end_timestamp cal_end INT DEFAULT NULL');
    }
}
