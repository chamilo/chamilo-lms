<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240425192900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Changes on the agenda_reminder structure';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('agenda_reminder')) {
            $tblAgendaReminder = $schema->getTable('agenda_reminder');

            if ($tblAgendaReminder->hasColumn('type')) {
                $this->addSql('ALTER TABLE agenda_reminder DROP type');
            }

            $this->addSql('ALTER TABLE agenda_reminder CHANGE id id INT AUTO_INCREMENT NOT NULL');

            if (!$tblAgendaReminder->hasForeignKey('FK_416FFA2471F7E88B')) {
                $this->addSql('DELETE FROM agenda_reminder WHERE event_id IS NULL OR event_id NOT IN (SELECT iid FROM c_calendar_event)');

                $this->addSql('ALTER TABLE agenda_reminder ADD CONSTRAINT FK_416FFA2471F7E88B FOREIGN KEY (event_id) REFERENCES c_calendar_event (iid)');
            }

            if (!$tblAgendaReminder->hasIndex('IDX_416FFA2471F7E88B')) {
                $this->addSql('CREATE INDEX IDX_416FFA2471F7E88B ON agenda_reminder (event_id)');
            }
        }
    }
}
