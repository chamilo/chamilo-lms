<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240308225800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Adds the auto_remove field to the extra_field table to manage automatic deletion during anonymization, if it does not exist.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('extra_field');
        if (!$table->hasColumn('auto_remove')) {
            $this->addSql('ALTER TABLE extra_field ADD auto_remove TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('extra_field');
        if ($table->hasColumn('auto_remove')) {
            $this->addSql('ALTER TABLE extra_field DROP COLUMN auto_remove');
        }
    }
}
