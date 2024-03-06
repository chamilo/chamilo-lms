<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240306133140 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove deprecated and confusing user.enabled field';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user');
        if ($table->hasColumn('enabled')) {
            $this->addSql('ALTER TABLE user DROP enabled');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user');
        if (!$table->hasColumn('enabled')) {
            $this->addSql('ALTER TABLE user ADD enabled TINYINT(1) NOT NULL');
        }
    }
}
