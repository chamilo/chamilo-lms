<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20170626122910 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove registration_date from user table and migrate data to created_at';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user SET created_at = registration_date WHERE created_at IS NULL');

        $this->addSql('ALTER TABLE user DROP COLUMN registration_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD registration_date DATETIME DEFAULT NULL');

        $this->addSql('UPDATE user SET registration_date = created_at WHERE registration_date IS NULL');
    }
}
