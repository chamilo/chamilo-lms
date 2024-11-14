<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20241113110000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add fulltext index to messages';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE FULLTEXT INDEX idx_message_search ON message (title, content)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_message_search ON message');
    }
}
