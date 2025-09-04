<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250904125000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename third_party.name to third_party.title';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE third_party CHANGE name title TEXT NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE third_party CHANGE title name TEXT NOT NULL;');
    }
}
