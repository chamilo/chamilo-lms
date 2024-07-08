<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201215072919 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create agenda_reminder table as fallback to allow to use CCalendarEvent entities';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('agenda_reminder')) {
            $this->addSql(
                "CREATE TABLE agenda_reminder (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, date_interval VARCHAR(255) NOT NULL COMMENT '(DC2Type:dateinterval)', sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)', INDEX IDX_416FFA2471F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC"
            );
            $this->addSql("ALTER TABLE agenda_reminder ADD CONSTRAINT FK_416FFA2471F7E88B FOREIGN KEY (event_id) REFERENCES c_calendar_event (iid)");
        }
    }
}