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
        return 'Alter "title" and "comment" fields in settings_current table';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('settings_current')) {
            $this->addSql('ALTER TABLE settings_current MODIFY COLUMN title TEXT NOT NULL');
            $this->addSql('ALTER TABLE settings_current MODIFY COLUMN comment TEXT');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('settings_current')) {
            $this->addSql('ALTER TABLE settings_current MODIFY COLUMN title VARCHAR(255) NOT NULL');
            $this->addSql('ALTER TABLE settings_current MODIFY COLUMN comment VARCHAR(255)');
        }
    }
}
