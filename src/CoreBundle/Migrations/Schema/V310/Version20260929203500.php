<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260929203500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Allow manual skill assignments to be removed without deleting their history.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('skill_rel_user')) {
            return;
        }

        $table = $schema->getTable('skill_rel_user');

        if (!$table->hasColumn('status')) {
            $this->addSql('ALTER TABLE skill_rel_user ADD status INT DEFAULT 1 NOT NULL');
        }

        if (!$table->hasColumn('last_status_update_user_id')) {
            $this->addSql('ALTER TABLE skill_rel_user ADD last_status_update_user_id INT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('skill_rel_user')) {
            return;
        }

        $table = $schema->getTable('skill_rel_user');

        if ($table->hasColumn('last_status_update_user_id')) {
            $this->addSql('ALTER TABLE skill_rel_user DROP last_status_update_user_id');
        }

        if ($table->hasColumn('status')) {
            $this->addSql('ALTER TABLE skill_rel_user DROP status');
        }
    }
}
