<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20211005065400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Plugins - bbb';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('plugin_bbb_meeting')) {
            $table = $schema->getTable('plugin_bbb_meeting');
            if (!$table->hasColumn('internal_meeting_id')) {
                $this->addSql('ALTER TABLE plugin_bbb_meeting ADD COLUMN internal_meeting_id VARCHAR(255) DEFAULT NULL;');
            }
        }

        if ($schema->hasTable('plugin_bbb_room')) {
            $table = $schema->getTable('plugin_bbb_room');
            if (!$table->hasColumn('close')) {
                $this->addSql('ALTER TABLE plugin_bbb_room ADD close INT NOT NULL DEFAULT 0;');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
