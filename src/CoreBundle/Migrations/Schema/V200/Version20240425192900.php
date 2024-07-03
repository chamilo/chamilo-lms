<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20240425192900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Changes on the agenda_reminder structure';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('agenda_reminder')) {
            $this->addSql(
                'CREATE TABLE agenda_reminder (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, date_interval VARCHAR(255) NOT NULL COMMENT "(DC2Type:dateinterval)", sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", updated_at DATETIME NOT NULL COMMENT "(DC2Type:datetime)", PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );
        } else {
            $this->addSql('ALTER TABLE agenda_reminder DROP type');
            $this->addSql('ALTER TABLE agenda_reminder CHANGE id id INT AUTO_INCREMENT NOT NULL;');
        }

        $this->addSql('ALTER TABLE agenda_reminder ADD CONSTRAINT FK_416FFA2471F7E88B FOREIGN KEY (event_id) REFERENCES c_calendar_event (iid)');
        $this->addSql('CREATE INDEX IDX_416FFA2471F7E88B ON agenda_reminder (event_id)');
    }
}
