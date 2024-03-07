<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240306204200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Modify user.active field from TINYINT to INT';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');
        if ($table->hasColumn('active')) {
            $this->addSql('ALTER TABLE user CHANGE active active INT NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');
        if ($table->hasColumn('active')) {
            $this->addSql('ALTER TABLE user CHANGE active active TINYINT(1) NOT NULL');
        }
    }
}
