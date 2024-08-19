<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240819120200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix message_receiver index on message_rel_user';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('message_rel_user');
        if ($table->hasIndex('message_receiver')) {
            $this->addSql('ALTER TABLE message_rel_user DROP INDEX message_receiver');
        }
        $this->addSql('ALTER TABLE message_rel_user ADD UNIQUE INDEX message_receiver (message_id, user_id, receiver_type)');
    }
    public function down(Schema $schema): void 
    {
        $table = $schema->getTable('message_rel_user');
        if ($table->hasIndex('message_receiver')) {
            $this->addSql('ALTER TABLE message_rel_user DROP INDEX message_receiver');
        }
        $this->addSql('ALTER TABLE message_rel_user ADD UNIQUE INDEX message_receiver (message_id, user_id)');

    }
}
