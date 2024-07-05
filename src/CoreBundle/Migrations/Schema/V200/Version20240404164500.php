<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240404164500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Alter "title" and "comment" fields in settings table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->addSql('ALTER TABLE settings MODIFY COLUMN title TEXT NOT NULL');
            $this->addSql('ALTER TABLE settings MODIFY COLUMN comment TEXT');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->addSql('ALTER TABLE settings MODIFY COLUMN title VARCHAR(255) NOT NULL');
            $this->addSql('ALTER TABLE settings MODIFY COLUMN comment VARCHAR(255)');
        }
    }
}
