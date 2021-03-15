<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20180904180000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate sys_calendar';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sys_calendar');

        $this->addSql('ALTER TABLE sys_calendar CHANGE access_url_id access_url_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_1670370E73444FD5')) {
            $this->addSql('ALTER TABLE sys_calendar ADD CONSTRAINT FK_1670370E73444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE');
        }

        if (!$table->hasIndex('IDX_1670370E73444FD5')) {
            $this->addSql('CREATE INDEX IDX_1670370E73444FD5 ON sys_calendar (access_url_id)');
        }

        $table = $schema->getTable('sys_announcement');

        $this->addSql('ALTER TABLE sys_announcement CHANGE access_url_id access_url_id INT DEFAULT NULL');
        if (!$table->hasForeignKey('FK_E4A3EAD473444FD5')) {
            $this->addSql(
                'ALTER TABLE sys_announcement ADD CONSTRAINT FK_E4A3EAD473444FD5 FOREIGN KEY (access_url_id) REFERENCES access_url (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasIndex('IDX_E4A3EAD473444FD5')) {
            $this->addSql('CREATE INDEX IDX_E4A3EAD473444FD5 ON sys_announcement (access_url_id)');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
